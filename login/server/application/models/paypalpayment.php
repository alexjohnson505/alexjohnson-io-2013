<?php


class PaypalPayment extends Model
{
    //access is checked by the invoice

    public $amount;
    private $paypal_url;
    private $ipn_status; // holds the last status
    public $admin_mail; // receive the ipn status report pre transaction
    public $paypal_mail; // paypal account, if set, class need to verify receiver
    public $txn_id; // array: if the txn_id array existed, class need to verified the txn_id duplicate
    public $ipn_log; // bool: log IPN results to text file?
    private $ipn_response; // holds the IPN response from paypal
    public $ipn_data = array(); // array contains the POST values for IPN
    public $sandbox;

    function __construct()
    {
        $is_sandbox = get_config('payments.is_sandbox');
        $paypal_url = 'https://www.paypal.com/cgi-bin/webscr';
        $paypal_sandbox_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
        $this->paypal_url = $is_sandbox == true ? $paypal_sandbox_url : $paypal_url;
    }

    // this function actually generates an entire HTML page consisting of
    // a form with hidden elements which is submitted to paypal via the
    // BODY element's onLoad attribute.  We do this so that you can validate
    // any POST vars from you custom form before submitting to paypal.  So
    // basically, you'll have your own form which is submitted to your script
    // to validate the data, which in turn calls this function to create
    // another hidden form and submit to paypal.

    // The user will briefly see a message on the screen that reads:
    // "Please wait, your order is being processed..." and then immediately
    // is redirected to paypal.
    public function generate_submission_button($invoice)
    {
        $form_url = $this->paypal_url;

        $business_email = get_config('payments.paypal.business_email');
        $language_code = get_config('payments.paypal.language_code');
        $item_name = get_config('company.name') . ' - Invoice #' . $invoice->number;
        $item_number = $invoice->number;
        $currency_code = get_config('payments.paypal.currency_code');
        $tax_rate = 0;
        $shipping = 0;

        $base_url = get_config('base_url');

        $return_url = $base_url . "#projects/$invoice->project_id/invoices/$invoice->id";
        $cancel_url = $base_url . "#projects/$invoice->project_id/invoices/$invoice->id";
        $ipn_handler_url = $base_url . "server/paypal/ipn_listener";
        $user_id = current_user()->id;

        // <input type='hidden' name='rm' value='2'>  =  Return method = POST
        $form = "<form action='$form_url' method='post' target='_parent'>
                    <input type='hidden' name='cmd' value='_xclick'>
                    <input type='hidden' name='rm' value='2'>
                    <input type='hidden' name='return' value='$return_url'>
                    <input type='hidden' name='cancel_return' value='$cancel_url'>
                    <input type='hidden' name='notify_url' value='$ipn_handler_url'>
                    <input type='hidden' name='business' value='$business_email'>
                    <input type='hidden' name='lc' value='$language_code'>
                    <input type='hidden' name='item_name' value='$item_name'>
                    <input type='hidden' name='item_number' value='$item_number'>
                    <input type='hidden' name='custom' value='$user_id'>
                    <input type='hidden' name='amount' value='$this->amount'>
                    <input type='hidden' name='currency_code' value='$currency_code'>
                    <input type='hidden' name='button_subtype' value='services'>
                    <input type='hidden' name='no_note' value='0'>
                    <input type='hidden' name='tax_rate' value='$tax_rate'>
                    <input type='hidden' name='shipping' value='$shipping'>
                    <input type='hidden' name='bn' value='PP-BuyNowBF:btn_buynowCC_LG.gif:NonHostedGuest'>
                    </form>";


        return $form;
    }

    function process_ipn()
    {
        //todo:make sure this works when the user isn't logged in
        $hostname = gethostbyaddr($_SERVER ['REMOTE_ADDR']);

        if (!preg_match('/paypal\.com$/', $hostname)) {
            $this->ipn_status = 'Validation post isn\'t from PayPal';
            //$this->log_ipn_results(false);
            return false;
        }

        // generate the post string from the _POST vars aswell as load the
        // _POST vars into an arry so we can play with them from the calling
        // script.
        $post_string = '';
        foreach ($_POST as $field => $value) {
            $this->ipn_data[$field] = $value;
            $value = urlencode(stripslashes($value));
            $post_string .= "$field=$value&";
        }
        $post_string .= "cmd=_notify-validate";

        $verification_url = $this->verification_url();
        // open the connection to paypal


        $fp = fsockopen($verification_url, "443", $err_num, $err_str, 60);

        if (!$fp) {
            // could not open the connection.
            $this->ipn_status = "fsockopen error no. $err_num: $err_str";
            $this->log_ipn_results(false);
            return false;
        } else {
            // parse the paypal URL
            $url_parsed = parse_url($this->paypal_url);

            // Post the data back to paypal
            fputs($fp, "POST $url_parsed[path] HTTP/1.1\r\n");
            fputs($fp, "Host: $url_parsed[host]\r\n");
            fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
            fputs($fp, "Content-length: " . strlen($post_string) . "\r\n");
            fputs($fp, "Connection: close\r\n\r\n");
            fputs($fp, $post_string . "\r\n\r\n");

            // loop through the response from the server and append to variable
            while (!feof($fp)) {
                $this->ipn_response .= fgets($fp, 1024);
            }
            fclose($fp); // close connection
        }

        // Invalid IPN transaction.  Check the $ipn_status and log for details.



        if (preg_match($this->ipn_response, "VERIFIED") == 0) {
            $this->generate_ipn_status_log(true);
            $this->record_successful_payment();
            return $this->ipn_data;

        } else {
            $this->generate_ipn_status_log(false);
            return false;
        }
    }

    function record_successful_payment()
    {
        $payment = new Payment();

        $payment->params_imported = true;

        $data = $this->ipn_data;

        $sql = 'SELECT * FROM invoices WHERE number = ' . $data['item_number'];

        $db = Database::getInstance();
        $record_set = $db->select($sql);
        $invoice = $record_set->export();
        //$invoice = $this->select($sql);

        $invoice = isset($invoice[0]) ? $invoice[0] : false;

        if($invoice){
            $invoice = new Invoice($invoice->id);

            $payment->set('amount', $data['payment_gross']);
            $payment->set('payment_processor_charge_id', $data['txn_id']);
            $payment->set('invoice_id', $invoice->id);
            $payment->set('user_id', $data['custom']);
        }


        try{
            $payment->set_primary_details($invoice, 'paypal');
            $payment->complete_payment($invoice, 'paypal', $data['custom']);
            $payment->send_payment_notification($invoice, 'paypal', $data['custom']);
        }
        catch(Exception $e){
            //todo: handle paypal ipn exception
        }
    }

    function verification_url()
    {
        return isset($_POST['test_ipn']) ? 'ssl://www.sandbox.paypal.com' : 'ssl://www.paypal.com';
    }

    private function
    generate_ipn_status_log($success)
    {
        $hostname = gethostbyaddr($_SERVER ['REMOTE_ADDR']);

        // Timestamp
        $date = '[' . date('m/d/Y g:i A') . '] - ';

        // Success or failure being logged?
        if ($success)
            $this->ipn_status = "$date SUCCESS: IPN VERIFIED!\n";
        else
            $this->ipn_status = "$date FAIL: IPN Verification Failed!\n";

        $this->ipn_status .= "[From: $hostname |" . $_SERVER ['REMOTE_ADDR'] .
                "]IPN POST Vars Received By Paypal_IPN Response API:\n";

        //Log each of the IPN post parameters
        foreach ($this->ipn_data as $key => $value) {
            $this->ipn_status .= "$key=$value \n";
        }

        // Log the response from the paypal server
        $this->ipn_status .= "IPN Response from Paypal Server:\n" . $this->ipn_response;

        if ((bool)get_config('payments.paypal.log_ipn_results') == true) {
            $email = get_config('company.email');

            //if the to and from email addresses are the same, the email will probably end up in the spam folder,
            //let's create an email address for the from address using the same domain as the to address
            $email_domain = explode('@', $email);
            $email_domain = $email_domain[1];
            $from = "ipnresults@$email_domain";

            $subject = 'IPN ' . $success ? ' VERIFIED' : 'FAILURE';
            $headers = 'To: ' . $email . "\r\n";
            $headers .= "From: 'IPN Results' <$from>" . "\r\n";
            mail($email, $subject, $this->ipn_status, $headers);
        }
    }

    //these functions don't actually do anything. Since paypal notifies us of a transaction asynchronously, the procces
    //flow for this type of payment is backwards. When we do hear from paypal via IPN, we will create a Payment model
    //with the IPN data, when we save this payment model, it will want to call init/submit (it does this for all payment
    //models)
    function init()
    {
    }

    function submit()
    {
        return true;
    }

    function get(){
        //override the default get function, since there is currently no functionality in the app to view a payment
        //or list payments
    }
}



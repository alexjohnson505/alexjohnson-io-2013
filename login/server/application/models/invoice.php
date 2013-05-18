<?php

use phpSweetPDO\SQLHelpers\Basic as Helpers;

Class Invoice extends Model
{
    public $client_id; //todo: resolve with client object
    public $project_id;
    public $number;
    public $date;
    public $tax_rate;
    public $subtotal;
    public $tax;
    public $total;
    public $payments;
    public $balance;
    public $due_date;
    public $date_sent;
    public $num_times_sent;
    public $status_text;
    public $is_overdue;
    public $is_paid;

    //params not saved to the db

    //the company object
    public $company;

    //the client object
    public $client;

    //array of InvoiceItems
    public $invoice_items;

    function validate(){
        $this->validator_tests = array(
            'client_id' => 'required',
            'project_id' => 'required',
            'number' => 'required',
            'date' => 'required',
            'due_date' => 'required'
        );

        return parent::validate();
    }

    function get($criteria = null){

        $sql = "SELECT invoices.*, clients.name AS client_name, projects.name AS project_name
                FROM invoices
                LEFT JOIN clients ON invoices.client_id = clients.id
                LEFT JOIN projects ON invoices.project_id = projects.id";

        if (is_numeric($criteria)) {
            $sql .= ' WHERE invoices.id = ' . $criteria;

            $invoice = parent::get_one($sql);

            global $CONFIG;
            $invoice['company'] = $CONFIG['company'];


            $invoice['client'] = $this->load('Client')->get($invoice['client_id']);
            $invoice['invoice_items'] = $this->load('InvoiceItem')->get($invoice['id']); //todo: if i were really using oop I would use invoice->id;

            $this->import_parameters($invoice);
            $this->update_status();
            //$invoice['status'] = $this->status;

            return $invoice;
        } else {
            $sql .= isset($criteria) ? " $criteria" : '';
            $sql = $this->modify_sql_for_user_type($sql, $criteria);

            $invoices = parent::get($sql);

            //we currently have an array of invoices where each invoice is an array itself. We need to update the status
            //of each invoice, which means creating an invoice object for each invoice array item
            foreach($invoices as &$invoice){
                //create the invoice
                $invoice_object = new Invoice($invoice);
//todo: i don't think this makes any sense because the invoice status will already be updated when I create the invoice using new Invoice

                //update the invoice status
                $invoice_object->update_status();

                //we need to add the status text back the invoice array, since we're sending the array to the client,
                //not the object
                $invoice['status_text'] = $invoice_object->status_text;
            }

            return $invoices;
        }
    }

    function set_client_id(){
        if (!isset($this->client_id)) {
            $project = new Project($this->project_id);
            $this->set('client_id', $project->client_id);
        }
    }

    function set_invoice_number(){
        if (!isset($this->number)) {
            $number = null;
            $result = $this->select("SELECT MAX(number)FROM invoices");

            if (is_null($result[0]['MAX(number)']))
                $number = $GLOBALS['CONFIG']['invoice']['base_invoice_number']; //todo:this isn't in the config
            else $number = $result[0]['MAX(number)'] + 1;

            $this->set('number', $number);
        }
    }

    function set_invoice_dates(){
        $hour = 12;
        $today = strtotime($hour . ':00:00');

        if(!isset($this->date))
            $this->set('date', $today);

        if(!isset($this->due_date))
            $this->set('due_date', $today);
    }

    function save($record_activity = true){

        if(!current_user()->is('admin'))
            return false;

        //we need to import the parameter that we're trying to save, in some cases this will be all of the invoice parameters,
        //but in others it will just be the project id. If it's just the project id, we will need to set the other
        //required parameters to their defaults
        $this->import_parameters();
        $this->set_client_id();
        $this->set_invoice_number();
        $this->set_invoice_dates();

        $this->validate();
        $item_result = true;

        //if there were no errors saving the base invoice, save each of the line items
        if (!$this->has_errors && is_array($this->invoice_items)) {

            foreach ($this->invoice_items as &$item) {
                //make sure the invoice item is tied to this invoice (just in case the invoice id isn't sent by the client)
                $invoice_item = new InvoiceItem($item);

                //let's just make sure the invoice item is correct, since we can't trust any of this client side data
                $invoice_item->set('invoice_id', $this->id);


                if (isset($invoice_item->task)) {
                    if(!$invoice_item->task instanceof Task){
                        $invoice_item->task = new Task($invoice_item->task);
                    }
                    //todo:does this still work?
                    $invoice_item->task->set('invoice_id', $this->id);
                    $invoice_item->task->save();
                }

                $item_result = $invoice_item->save();

                //calculate total is going to need the object
                $item = $invoice_item;
                //stop saving line items if there was an error saving this one
                if ($invoice_item->has_errors)
                    break;
            }
        }

        //let's store is_new status in a variable because it will change once we call save
        $is_new = $this->is_new();

        //there is no reason to calculate the total if this is a new invoice because there won't be any invoice items
        //yet
        if(!$is_new){
            $this->calculate_total();
        }

        $this->update_status(false);

        $result = parent::save();

        if($record_activity){
            if ($is_new)
                $activity_action = 'created';
            else $activity_action = 'updated';

            //todo:some saves shouldn't generate an activity item
            new Activity($this, $activity_action, '#' . $this->number);
        }

        //return the result, true or false
        return $is_new ? $result : ($item_result != false);
    }

    function send(){
        $email = new AppEmail();


        if($email->send_invoice($this->client_id, $this)){
            //update the invoice with the most recent send date
            $this->clear_params(); //todo:I have no idea why the params array is even populated at this point, but
            $this->set('date_sent', time());
            $this->set('num_times_sent', intval($this->num_times_sent) + 1);
            $this->params_imported = true; //todo: i need to do something about this.
            parent::save();
            return true;
        }
        else return false;
    }

    function has_balance(){
        return $this->balance > 0;
    }

    function valid_payment_amount($amount){
        $old_balance = $this->balance;
        //recalculate the invoice total, which will update the balance
        $this->calculate_total();

        //using parent::save, because there is no need for us to save the invoice items in this case
        if($old_balance != $this->balance){
            parent::save();
        }

        if($this->has_balance() && (float)$this->balance >= (float)$amount)
            return true;
        else return false;
    }

    function calculate_total($save_invoice = false){
        $subtotal = 0;
        $tax = 0;
        $total = 0;
        $payments = 0;
        $balance = 0;

        if(is_array($this->invoice_items)){
            //calculate the subtotal
            foreach ($this->invoice_items as $item) {
                $subtotal += $item->quantity * $item->rate;
            }
        }


        //set the tax rate, calculate tax and total
        $tax_rate = isset($this->tax_rate) ? $this->tax_rate : $this->set('tax_rate', get_config('invoice.tax_rate'));
        $tax = $subtotal * $tax_rate;
        $total = round($subtotal + $tax, 2);

        //calculate the total value of all payments made on this invoice
        $payment_transactions = $this->get_payments();
        foreach($payment_transactions as $payment){
            $payments += $payment->amount;
        }

        //calculate balance
        $balance = $total - $payments;

        //set all total related values
        $this->set('subtotal', $subtotal);
        $this->set('tax', $tax);
        $this->set('total', $total);
        $this->set('payments', $payments);
        $this->set('balance', $balance);

        if($save_invoice)
            parent::save();
    }

    function update_status($save_status = true){

        //store the current invoice status as it exists in the database
        $old_status = $this->status_text;

        if($this->total <= 0){
            $this->set('status_text', 'Inactive');
            $this->set('is_overdue', false);
            $this->set('is_paid', false);
        }
        else{
            if($this->balance <= 0)
            {
                $this->set('status_text', 'Paid');
                $this->set('is_overdue', false);
                $this->set('is_paid', true);
            } //todo:language file
            else if ($this->due_date < time())
            {
                $this->set('status_text', 'Overdue');
                $this->set('is_overdue', true);
                $this->set('is_paid', false);
            } //todo:language file
            else {
                $this->set('status_text', 'Pending');
                $this->set('is_overdue', false);
                $this->set('is_paid', false);
            }
        }

        //if the old status and the new status do not match, then we need to save this invoice back to the database.
        if(($save_status == true) && ($old_status != (string)$this->status_text)){
           parent::save();
        }
    }

    function get_payments(){
        $payment_class = new Payment();
        $payments = $payment_class->get("WHERE invoice_id = $this->id");

        return is_array($payments) ? $payments : array();
    }


    function delete(){
        $result = parent::delete();

        new Activity($this, 'deleted', $this->number);

        return $result;
    }

    function current_user_can_access(){
        //todo:permission needs to be more fine grained. Clients can view but not create, edit, or delete invoices
        $user = current_user();

        if($user->role == 'admin' || $user->client_id == $this->client_id)
            return true;
        else return false;
    }
}
 

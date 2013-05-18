<?php


class Email extends Model{
    public $message;
    public $recipient;
    public $sender_email;
    public $sender_name;
    public $subject;



    function generate($template, $data = null){
       // $data = $this->decode($data);
        $this->load_library('rain.tpl.class');

        raintpl::$tpl_dir = ROOT . "/application/email-templates/"; // template directory

        $tpl = new raintpl(); //include Rain TPL

        if(isset($data))
            $tpl->assign($data); // associative array

        if(!get_config('email.debug_templates'))
            $this->message = @$tpl->draw($template, true); // draw the template
        else $this->message = $tpl->draw($template, true); // draw the template
    }

    function decode($data) {

        if(is_array($data)){
            foreach($data as &$value){
                if(!is_object($value) && !is_array($value))
                    $value = html_entity_decode($value);
            }
        }

        return $data;
    }

    function validate(){
        if(!isset($this->recipient) || !isset($this->message))
            return false;

        if(!isset($this->sender_name))
            $this->set_sender_name();

        if(!isset($this->sender_email))
            $this->set_sender_email();

        if (!isset($this->subject))
            $this->set_subject();

        return true;
    }

    function send(){

        $this->validate();

        if(get_config('email.use_smtp') == true){
            return $this->send_smtp_email();
        }
        else{
            return $this->send_email();
        }
    }

    function send_smtp_email(){
        $this->load_library('phpmailer/class.phpmailer');

        $mail = new PHPMailer;

        $mail->IsSMTP();                                                // Set mailer to use SMTP
        $mail->Host = get_config('email.host');                         // Specify main and backup server
        $mail->Port = get_config('email.port');
        $mail->SMTPAuth = get_config('email.enable_authentication');    // Enable SMTP authentication
        $mail->Username = get_config('email.username');                 // SMTP username
        $mail->Password = get_config('email.password');                 // SMTP password
        $mail->SMTPSecure = get_config('email.enable_encryption');      // Enable encryption, 'ssl' also accepted

        $mail->From = $this->sender_email;
        $mail->FromName = $this->sender_name;
        $mail->AddAddress($this->recipient);  // Add a recipient

        $mail->IsHTML(true);    // Set email format to HTML

        $mail->Subject = $this->subject;
        $mail->Body    = $this->message;
        //todo:text version
        //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

        if(!$mail->Send()) {
            return false;
        }
        else return true;
    }

    function send_email(){
        // To send HTML mail, the Content-type header must be set
        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

        // Additional headers
        $headers .= 'To: ' . $this->recipient . "\r\n";
        $headers .= "From: $this->sender_name <$this->sender_email>" . "\r\n";

        //send the email
        $result = mail($this->recipient, $this->subject, $this->message, $headers);

        return $result;
    }

    function set_recipient($recipient){
        $this->recipient = $recipient;
    }

    function set_sender_email($sender_email = null){
        $this->sender_email = isset($sender_email) ? $sender_email : get_config('company.email');
    }

    function set_sender_name($sender_name = null){
        $this->sender_name = isset($sender_name) ? $sender_name : get_config('company.name');
    }

    function set_subject($subject = null){
        $this->subject = isset($subject) ? $subject : get_config('email.default_subject');
    }
}
 

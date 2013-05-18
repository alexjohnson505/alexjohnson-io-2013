<?php
/**
 * Created by 23rd & Walnut
 * User: saleem
 * Date: 5/12/12
 * Time: 7:03 PM
 * www.23andwalnut.com
 */
 
Class Response{
    private $code;
    private $data;
    private $auth;
    private $user;

    function __construct($data = null, $code = 200){
        $user = current_user();

        $this->user = $user != false ? $user->client_side() : false;

        if(isset($data)){
            $this->data = $data;
            $this->auth = 'continue';
            $this->code = $code;
            $this->render();
        }

        return $this;
    }

    public function not_logged_in(){
        $this->auth_status('not_logged_in');
    }

    public function auto_logged_out(){
        $this->auth_status('auto_logged_out');
    }

    public function logged_in(){
         $user = current_user();

         $this->data = array(
             'first_name' => $user->first_name,
             'last_name' => $user->last_name
         );

         $this->auth_status('logged_in');
    }

    public function not_authorized(){
        $this->auth_status('not_authorized');
    }

    public function successful_login(){
         $this->auth_status('successful_login');
    }

    public function unsuccessful_login(){
        $this->auth_status('unsuccessful_login');
    }

    public function error($text){
        $this->code = 400;
        $this->data = $text;
        $this->render();
    }

    public function invalid_model($id){
        $this->error('Invalid model id: ' . $id);
    }

    function auth_status($status){
        $this->code = 200;
        $this->auth = $status;
        $this->render();
    }

    function render(){
        global $CONFIG;

        $response = array(
            'code' => $this->code,
            'data' => $this->decode($this->data),
            'auth' => $this->auth,
            'user' => $this->user,
            //added specifically so the login screen can display the company name (enen if the user isn't logged in)
            'company' => $CONFIG['company']['name']
        );

        Model::send_sql_log();
        //todo:convert everything to camel case here for the js. Right now all properties are still using underline syntax my_var_name
        echo json_encode($response);

        exit;
    }

    function decode($data) {
        if(is_object($data) || is_array($data)){
            foreach($data as &$value)
                $value = $this->decode($value);
        }
        else $data = html_entity_decode($data);

        return $data;
    }
}
<?php

global $CONFIG;
global $CS_CONFIG;

$CONFIG = array();
$CS_CONFIG = array();

//DEBUGGING
$CONFIG['enable_debugging'] = false;
define('DEVELOPMENT_ENVIRONMENT', $CONFIG['enable_debugging']);

//DATABASE CONNECTION
define('DB_NAME', 'alexio_duet_app');
define('DB_USER', 'alexio_duet');
define('DB_PASSWORD', '%TDcK%ANzG7K');
define('DB_HOST', 'localhost');

//BASE URL
$CONFIG['base_url'] = 'http://alexjohnson.io/login/';

//COMPANY
$CONFIG['company']['name'] = 'alexjohnson.io';
$CONFIG['company']['address1'] = '';
$CONFIG['company']['address2'] = '';
$CONFIG['company']['email'] = 'alexjohnson505@gmail.com';
$CONFIG['company']['phone'] = '';
$CONFIG['company']['website'] = '';

$CONFIG['company']['logo'] = $CONFIG['base_url'] . '/client/images/sample-logo.png';

//EMAIL SETTINGS
$CONFIG['email']['use_smtp'] = false;
$CONFIG['email']['host'] = '';
$CONFIG['email']['port'] = 465;
$CONFIG['email']['enable_authentication'] = true;
$CONFIG['email']['username'] = '';
$CONFIG['email']['password'] = '';
$CONFIG['email']['enable_encryption'] = 'ssl';

//other email settings
$CONFIG['email']['default_subject'] = $CONFIG['company']['name'] . ' sent you a message';
$CONFIG['email']['new_account_subject'] = "Your new account with " . $CONFIG['company']['name'];
$CONFIG['email']['new_invoice_subject'] = "You've received an invoice from " . $CONFIG['company']['name'];
$CONFIG['email']['forgot_password_subject'] = "Your temporary password";
$CONFIG['email']['changed_password_subject'] = "Your password has been changed";
$CONFIG['email']['client_payment_subject'] = "Online Payment";
$CONFIG['email']['admin_payment_subject'] = "A payment has been recieved";
$CONFIG['email']['message_subject'] = "A message has been posted on a project";
$CONFIG['email']['uploaded_file_subject'] = "A file has been uploaded to a project";
$CONFIG['email']['task_assignment_subject'] = "A task has been assigned to you";

$CONFIG['email']['debug_templates'] = false;

//INVOICES
$CONFIG['invoice']['base_invoice_number'] = 201000;
$CONFIG['invoice']['tax_rate'] = .01;
$CONFIG['invoice']['default_terms'] = 'All totals are final and non-negotiable. Payments must be made by the specified due date with no exceptions. Mailed checks must be postmarked by the due date above.';

//TASKS
$CONFIG['task']['at_risk_timeframe'] = 2;

//UPLOADS
$CONFIG['uploads']['folder_name'] = 'files-folder';
$CONFIG['uploads']['path'] = ROOT . '/' . $CONFIG['uploads']['folder_name'] . '/';
$CONFIG['uploads']['web_path'] = $CONFIG['base_url'] . 'server/' .  $CONFIG['uploads']['folder_name'] . '/';

$CONFIG['uploads']['user_images_folder_name'] = 'user_images';
$CONFIG['uploads']['user_images_path'] = ROOT . '/' . $CONFIG['uploads']['folder_name'] . '/' . $CONFIG['uploads']['user_images_folder_name'] . '/';
$CONFIG['uploads']['user_images_web_path'] = $CONFIG['base_url'] . 'server/' . $CONFIG['uploads']['folder_name'] . '/' . $CONFIG['uploads']['user_images_folder_name'] . '/';

$CONFIG['uploads']['max_file_size'] = 200000000;
$CONFIG['uploads']['allow_client_uploads'] = true;

//PAYMENTS
$CONFIG['currency_symbol'] = '$';

$CONFIG['payments']['method'] = 'none';
$CONFIG['payments']['is_sandbox'] = true;
$CONFIG['payments']['payment_instructions'] = 'Please contact an administrator for instructions on submitting your payment';

// set your secret key: remember to change this to your live secret key in production
// see your keys here https://manage.stripe.com/account
$CONFIG['payments']['stripe']['publishable_key'] = '';
$CONFIG['payments']['stripe']['secret_key'] = '';

$CONFIG['payments']['paypal']['business_email'] = '';
$CONFIG['payments']['paypal']['language_code'] = '';
$CONFIG['payments']['paypal']['currency_code'] = '';

//AUTO LOGOUT
$CONFIG['auto_logout']['is_enabled'] = false;
//1800 = 30 mins
$CONFIG['auto_logout']['max_inactivity'] = 1800;

//DEFAULT SERVER SIDE ROUTE
$CONFIG['default_route_controller'] = 'portal';
$CONFIG['default_route_action'] = 'home';
$CONFIG['default_action'] = 'get';

//PURCHASE CODE
$CONFIG['purchase_code'] = 'e5083c33-c03f-4a1e-bb97-46a86a8a3c21';

//PUBLIC ROUTES
//Routes in this array can be accessible to the public (the user does not need to be logged in)
$CONFIG['public_routes'] = array(
    'paypal/ipn_listener',
    'app/config',
    //the client side route
    'forgot_password',
    //the server side route
    'user/forgot_password'
);

//RESTRICTED ROUTES
//There is some functionality that shouldn't be exposed regardless of whether the user is logged in,
//Routes in this array can not be accessed. Using any functionality on these models requires calling directly in another model
//i.e upload is called by the file model
$CONFIG['restricted_routes'] = array(
    'upload/*',
    'stripepayment/*',
    'paypalpayment/get',
    'payment/get',
    'tasksmanager/get'
);

//USER PLACEHOLDER IMAGES
$CONFIG['unknown_user'] = 'client/images/unknown-user-big.jpg';
$CONFIG['unknown_user_thumb'] = 'client/images/unknown-user.jpg';

//MORE DEBUGGING OPTIONS
//will email a list of all queries for a particular request. If you would like to change the logging behaviour you can modify it in
//core/model.class.php
$CONFIG['log_queries'] = false;

//useful for debugging paypal IPN functionality. This logging functionality will simply email a copy of the ipn data to
//the admin email specified in this config file. If you would like to change the logging behaviour you can modify it in
//application/models/paypalpayment.php
$CONFIG['payments']['paypal']['log_ipn_results'] = false;

//CLIENT SIDE CONFIG
//Config values necessary for the client side (javascript) code
//DO NOT PLACE ANY SENSITIVE INFORMATION IN THESE VARIABLES
$CS_CONFIG['payment_method'] = $CONFIG['payments']['method'];
$CS_CONFIG['stripe_publishable_key'] = $CONFIG['payments']['stripe']['publishable_key'];
$CS_CONFIG['currency_symbol'] = $CONFIG['currency_symbol'];
$CS_CONFIG['tax_rate'] = $CONFIG['invoice']['tax_rate'];

//Determines the format to show in the files view
//Valid values are Tiles or LineItems
$CS_CONFIG['default_file_view'] = 'Tiles';
$CS_CONFIG['default_dashboard_projects_view'] = 'Tiles';
$CS_CONFIG['default_route'] = 'dashboard';
$CS_CONFIG['company_name'] = $CONFIG['company']['name'];
$CS_CONFIG['task_timer_save_interval'] = 3;
$CS_CONFIG['public_routes'] = $CONFIG['public_routes'];
$CS_CONFIG['allow_client_uploads'] = $CONFIG['uploads']['allow_client_uploads'];
$CS_CONFIG['enable_debugging'] = $CONFIG['enable_debugging'];


//we only want the payment instructions variable set if the payment method is 'none' or an invalid value
if($CONFIG['payments']['method'] != 'paypal' && $CONFIG['payments']['method'] != 'stripe')
    $CS_CONFIG['payment_instructions'] = $CONFIG['payments']['payment_instructions'];

?>
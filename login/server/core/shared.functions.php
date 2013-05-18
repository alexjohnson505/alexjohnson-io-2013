<?php


/**
 *Check if environment is development
 */

function set_reporting()
{
    if (DEVELOPMENT_ENVIRONMENT == true) {
        error_reporting(E_ALL);
        ini_set('display_errors', 'On');

        enable_debugging();
    } else {
        error_reporting(E_ALL);
        ini_set('display_errors', 'Off');
        ini_set('log_errors', 'On');
        ini_set('error_log', ROOT . DS . 'tmp' . DS . 'logs' . DS . 'error.log');
    }
}

function enable_debugging(){
    require_once('debug/ChromePhp.php');
}


/**
 * Check for Magic Quotes and remove them
 */
function strip_slashes_deep($value)
{
    $value = is_array($value) ? array_map('strip_slashes_deep', $value) : stripslashes($value);
    return $value;
}

function remove_magic_quotes()
{
    if (get_magic_quotes_gpc()) {
        $_GET = strip_slashes_deep($_GET);
        $_POST = strip_slashes_deep($_POST);
        $_COOKIE = strip_slashes_deep($_COOKIE);
    }
}


function pre($data, $return = false)
{
    if(!$return){
        echo "<pre>";
        print_r($data);
        echo "</pre>";

        return true;
    }
    else {
        return "<pre>" . print_r($data, true) . "</pre>";
    }
}


function get_config($option)
{

    global $CONFIG;

    $error = false;
    $optionArr = explode('.', $option);
    $optionStructure = $CONFIG;

    //determine if the specified option exists on the options structure, if so get a reference to it
    for ($i = 0; $i < count($optionArr); $i++) {
        if (isset($optionStructure[$optionArr[$i]]))
            $optionStructure = $optionStructure[$optionArr[$i]];
        else {
            //The specified parameter does not exist on the options object
            $error = true;
            $optionStructure = false;
        }
    }


    if(!$error)
        return $optionStructure;
    else return false;


}

/**
 * This function autoloads the controller
 * for the call hook function
 */
function __autoload($className)
{
    if (file_exists(ROOT . DS . 'core' . DS . strtolower($className) . '.class.php')) {
        require_once(ROOT . DS . 'core' . DS . strtolower($className) . '.class.php');
    } else {
        $filename_prefix = explode('Controller', $className);
        $filename_prefix = $filename_prefix[0];

        if (file_exists(ROOT . DS . 'application' . DS . 'controllers' . DS . strtolower($filename_prefix) . '.controller.php')) {
            require_once(ROOT . DS . 'application' . DS . 'controllers' . DS . strtolower($filename_prefix) . '.controller.php');
        } else if (file_exists(ROOT . DS . 'application' . DS . 'models' . DS . strtolower($className) . '.php')) {
            require_once(ROOT . DS . 'application' . DS . 'models' . DS . strtolower($className) . '.php');
        }
    }

}

function is_xhr()
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

//since php doesn't allow chaining off of a constructor
function Response($data = null, $code = null)
{
    $code = isset($code) ? $code : 200;
    return new Response($data, $code);
}


function can($action, $resource)
{
    return Authority::can($action, $resource);
}

function current_user()
{
    return isset($_SESSION['current_user']) ? $_SESSION['current_user'] : Controller::current_user();
}






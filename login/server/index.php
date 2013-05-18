<?php
define('DS', DIRECTORY_SEPARATOR);
define('ROOT',dirname(__FILE__));

//TODO:namespace this stuff
require_once ('config' . DS . 'config.php');
require_once ('core' . DS . 'shared.functions.php');
require_once ('core' . DS . 'request.class.php');

set_reporting();
remove_magic_quotes();
new Request();
?>
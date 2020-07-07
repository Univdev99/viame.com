<?php
/* -- START -- */
//echo 'hi under maintenance';
//exit;

if(isset($_GET['newsletter']) && $_GET['newsletter'] == 1) {
	
	include_once("../../access.smallcapnetwork.com/index.php");
	exit;
}

// Path to Application - Use Full Path
defined('APPLICATION_PATH') || define('APPLICATION_PATH', '/opt/websites/viame.com/application');

// Define application environment
# development, testing, staging, production
if (preg_match("/(dev\.viame\.com|internal\.smallcapnetwork\.com|internal\.ninjaplays\.com)$/i", $_SERVER['SERVER_NAME'])) 
{ 
	putenv('APPLICATION_ENV=development'); 
}
#if ($_SERVER['REMOTE_ADDR'] == '107.200.91.222') { putenv('APPLICATION_ENV=testing'); }
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Set the Include Path
set_include_path(implode(PATH_SEPARATOR, array(
    APPLICATION_PATH . '/library/',
    #APPLICATION_PATH . '/library/Other/PayPal/lib/',
    get_include_path()
)));

// Zend_Application
require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/config/config.xml'
);

$application->bootstrap()->run();
            


/* -- END -- */

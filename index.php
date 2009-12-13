<?php
/***********************************************************************
| PortSensor(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2009, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| By using this software, you acknowledge having read the license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.portsensor.com	  http://www.webgroupmedia.com/
***********************************************************************/

if(version_compare(PHP_VERSION, "5.2", "<"))
	die("PortSensor requires PHP 5.2 or later.");

require(getcwd() . '/framework.config.php');
require(DEVBLOCKS_PATH . 'Devblocks.class.php');

// If this is our first run, redirect to the installer
if('' == APP_DB_DRIVER 
	|| '' == APP_DB_HOST 
	|| '' == APP_DB_DATABASE 
	|| null == ($db = DevblocksPlatform::getDatabaseService())
	|| DevblocksPlatform::isDatabaseEmpty()) {
   		header('Location: '.dirname($_SERVER['PHP_SELF']).'/install/index.php'); // [TODO] change this to a meta redirect
   		exit;
	}

require(APP_PATH . '/api/Application.class.php');

DevblocksPlatform::init();
DevblocksPlatform::setExtensionDelegate('PS_DevblocksExtensionDelegate');

// Request
$request = DevblocksPlatform::readRequest();

// Patches (if not on the patch page)
if(@0 != strcasecmp(@$request->path[0],"update")
	&& !DevblocksPlatform::versionConsistencyCheck())
	DevblocksPlatform::redirect(new DevblocksHttpResponse(array('update','locked')));

//DevblocksPlatform::readPlugins();
$session = DevblocksPlatform::getSessionService();

// Localization
DevblocksPlatform::setLocale((isset($_SESSION['locale']) && !empty($_SESSION['locale'])) ? $_SESSION['locale'] : 'en_US');
if(isset($_SESSION['timezone'])) @date_default_timezone_set($_SESSION['timezone']);

// Initialize Logging
if(method_exists('DevblocksPlatform','getConsoleLog')) {
	$timeout = ini_get('max_execution_time');
	$logger = DevblocksPlatform::getConsoleLog();
	$logger->info("[Devblocks] ** Platform starting (".date("r").") **");
	$logger->info('[Devblocks] Time Limit: '. (($timeout) ? $timeout : 'unlimited') ." secs");
	$logger->info('[Devblocks] Memory Limit: '. ini_get('memory_limit'));
}

// [JAS]: HTTP Request (App->Platform)
PortSensorApplication::processRequest($request);

exit;

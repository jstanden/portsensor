<?php
require(getcwd() . '/framework.config.php');
require(DEVBLOCKS_PATH . 'Devblocks.class.php');
require(APP_PATH . '/api/Application.class.php');

header("Content-type: text/html; charset=".LANG_CHARSET_CODE);

$request = DevblocksPlatform::readRequest();

DevblocksPlatform::init();
DevblocksPlatform::setExtensionDelegate('PS_DevblocksExtensionDelegate');

$session = DevblocksPlatform::getSessionService();
$settings = PortSensorSettings::getInstance();
$worker = PortSensorApplication::getActiveWorker();

// Localization
DevblocksPlatform::setLocale((isset($_SESSION['locale']) && !empty($_SESSION['locale'])) ? $_SESSION['locale'] : 'en_US');
if(isset($_SESSION['timezone'])) @date_default_timezone_set($_SESSION['timezone']);

$tpl = DevblocksPlatform::getTemplateService();
$tpl->assign('translate', DevblocksPlatform::getTranslationService());
$tpl->assign('session', $_SESSION);
$tpl->assign('visit', $session->getVisit());
$tpl->assign('active_worker', $worker);
$tpl->assign('settings', $settings);
$tpl->assign('core_tpl', DEVBLOCKS_PLUGIN_PATH . 'portsensor.core/templates/');

if(!empty($worker)) {
//	$active_worker_memberships = $worker->getMemberships();
//	$tpl->assign('active_worker_memberships', $active_worker_memberships);
	
//	$keyboard_shortcuts = intval(DAO_WorkerPref::get($worker->id,'keyboard_shortcuts', 1));
//	$tpl->assign('pref_keyboard_shortcuts', $keyboard_shortcuts);
}

PortSensorApplication::processRequest($request,true);

exit;

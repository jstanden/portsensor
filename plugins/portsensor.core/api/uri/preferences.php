<?php
class PsPreferencesPage extends PortSensorPageExtension {
	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
		parent::__construct($manifest);
	}
	
	function isVisible() {
		// check login
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	function render() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = $this->_TPL_PATH;
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";

		$response = DevblocksPlatform::getHttpResponse();
		$path = $response->path;
		
		array_shift($path); // preferences
		
		$tab_manifests = DevblocksPlatform::getExtensions('portsensor.preferences.tab', false);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		@$section = array_shift($path); // section
		switch($section) {
		    default:
		    	$tpl->assign('tab', $section);
				$tpl->display('file:' . $tpl_path . 'preferences/index.tpl');
				break;
		}
	}
	
	// Ajax
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_PreferenceTab) {
			$inst->showTab();
		}
	}
	
	// Post
	function saveTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_PreferenceTab) {
			$inst->saveTab();
		}
	}
	
	/*
	 * [TODO] Proxy any func requests to be handled by the tab directly, 
	 * instead of forcing tabs to implement controllers.  This should check 
	 * for the *Action() functions just as a handleRequest would
	 */
	function handleTabActionAction() {
		@$tab = DevblocksPlatform::importGPC($_REQUEST['tab'],'string','');
		@$action = DevblocksPlatform::importGPC($_REQUEST['action'],'string','');

		if(null != ($tab_mft = DevblocksPlatform::getExtension($tab)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_PreferenceTab) {
				if(method_exists($inst,$action.'Action')) {
					call_user_func(array(&$inst, $action.'Action'));
				}
		}
	}
	
	// Ajax [TODO] This should probably turn into Extension_PreferenceTab
	function showGeneralAction() {
		$date_service = DevblocksPlatform::getDateService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = $this->_TPL_PATH;
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";
		
		$worker = PortSensorApplication::getActiveWorker();
		$tpl->assign('worker', $worker);
		
//		$tour_enabled = intval(DAO_WorkerPref::get($worker->id, 'assist_mode', 1));
//		$tpl->assign('assist_mode', $tour_enabled);
//
//		$keyboard_shortcuts = intval(DAO_WorkerPref::get($worker->id, 'keyboard_shortcuts', 1));
//		$tpl->assign('keyboard_shortcuts', $keyboard_shortcuts);
//
//		$mail_inline_comments = DAO_WorkerPref::get($worker->id,'mail_inline_comments',1);
//		$tpl->assign('mail_inline_comments', $mail_inline_comments);
//		
//		$mail_always_show_all = DAO_WorkerPref::get($worker->id,'mail_always_show_all',0);
//		$tpl->assign('mail_always_show_all', $mail_always_show_all);
//		
//		$addresses = DAO_AddressToWorker::getByWorker($worker->id);
//		$tpl->assign('addresses', $addresses);
//				
//		// Timezones
//		$tpl->assign('timezones', $date_service->getTimezones());
//		@$server_timezone = date_default_timezone_get();
//		$tpl->assign('server_timezone', $server_timezone);
//		
//		// Languages
//		$langs = DAO_Translation::getDefinedLangCodes();
//		$tpl->assign('langs', $langs);
//		$tpl->assign('selected_language', DAO_WorkerPref::get($worker->id,'locale','en_US')); 
		
		$tpl->display('file:' . $tpl_path . 'preferences/tabs/general.tpl');
	}
	
	// Post [TODO] This should probably turn into Extension_PreferenceTab
	function saveDefaultsAction() {
//		@$timezone = DevblocksPlatform::importGPC($_REQUEST['timezone'],'string');
//		@$lang_code = DevblocksPlatform::importGPC($_REQUEST['lang_code'],'string','en_US');
//		@$default_signature = DevblocksPlatform::importGPC($_REQUEST['default_signature'],'string');
//		@$default_signature_pos = DevblocksPlatform::importGPC($_REQUEST['default_signature_pos'],'integer',0);
//		@$reply_box_height = DevblocksPlatform::importGPC($_REQUEST['reply_box_height'],'integer');
	    
		$worker = PortSensorApplication::getActiveWorker();
		$translate = DevblocksPlatform::getTranslationService();
   		$tpl = DevblocksPlatform::getTemplateService();
   		
   		// Time
   		$_SESSION['timezone'] = $timezone;
   		@date_default_timezone_set($timezone);
//   		DAO_WorkerPref::set($worker->id,'timezone',$timezone);
   		
   		// Language
   		$_SESSION['locale'] = $lang_code;
   		DevblocksPlatform::setLocale($lang_code);
//   		DAO_WorkerPref::set($worker->id,'locale',$lang_code);
   		
		@$new_password = DevblocksPlatform::importGPC($_REQUEST['change_pass'],'string');
		@$verify_password = DevblocksPlatform::importGPC($_REQUEST['change_pass_verify'],'string');
    	
		//[mdf] if nonempty passwords match, update worker's password
		if($new_password != "" && $new_password===$verify_password) {
			$session = DevblocksPlatform::getSessionService();
			$fields = array(
				DAO_Worker::PASS => md5($new_password)
			);
			DAO_Worker::update($worker->id, $fields);
		}

//		@$assist_mode = DevblocksPlatform::importGPC($_REQUEST['assist_mode'],'integer',0);
//		DAO_WorkerPref::set($worker->id, 'assist_mode', $assist_mode);
//
//		@$keyboard_shortcuts = DevblocksPlatform::importGPC($_REQUEST['keyboard_shortcuts'],'integer',0);
//		DAO_WorkerPref::set($worker->id, 'keyboard_shortcuts', $keyboard_shortcuts);
//
//		@$mail_inline_comments = DevblocksPlatform::importGPC($_REQUEST['mail_inline_comments'],'integer',0);
//		DAO_WorkerPref::set($worker->id, 'mail_inline_comments', $mail_inline_comments);
//		
//		@$mail_always_show_all = DevblocksPlatform::importGPC($_REQUEST['mail_always_show_all'],'integer',0);
//		DAO_WorkerPref::set($worker->id, 'mail_always_show_all', $mail_always_show_all);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('preferences')));
	}
	
};

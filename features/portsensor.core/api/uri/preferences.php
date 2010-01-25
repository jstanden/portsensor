<?php
class PsPreferencesPage extends PortSensorPageExtension {
	private $_TPL_PATH = '';
	
	const VIEW_MY_ALERTS = 'my_alerts';
	
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

		$response = DevblocksPlatform::getHttpResponse();
		$path = $response->path;
		
		array_shift($path); // preferences
		
		$tab_manifests = DevblocksPlatform::getExtensions('portsensor.preferences.tab', false);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		@$section = array_shift($path); // section
		$tpl->assign('tab_selected', $section);
		
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
	function showTabGeneralAction() {
		$date_service = DevblocksPlatform::getDateService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = $this->_TPL_PATH;
		$tpl->assign('path', $tpl_path);
		
		$worker = PortSensorApplication::getActiveWorker();
		$tpl->assign('worker', $worker);
		
		$assist_mode = intval(DAO_WorkerPref::get($worker->id, 'assist_mode', 1));
		$tpl->assign('assist_mode', $assist_mode);

		$keyboard_shortcuts = intval(DAO_WorkerPref::get($worker->id, 'keyboard_shortcuts', 1));
		$tpl->assign('keyboard_shortcuts', $keyboard_shortcuts);

		// Timezones
		$tpl->assign('timezones', $date_service->getTimezones());
		@$server_timezone = date_default_timezone_get();
		$tpl->assign('server_timezone', $server_timezone);
		
		// Languages
		$langs = DAO_Translation::getDefinedLangCodes();
		$tpl->assign('langs', $langs);
		$tpl->assign('selected_language', DAO_WorkerPref::get($worker->id,'locale','en_US')); 
		
		$tpl->display('file:' . $tpl_path . 'preferences/tabs/general.tpl');
	}
	
	// Post [TODO] This should probably turn into Extension_PreferenceTab
	function saveTabGeneralAction() {
		@$timezone = DevblocksPlatform::importGPC($_REQUEST['timezone'],'string');
		@$lang_code = DevblocksPlatform::importGPC($_REQUEST['lang_code'],'string','en_US');
	    
		$worker = PortSensorApplication::getActiveWorker();
		$translate = DevblocksPlatform::getTranslationService();
   		$tpl = DevblocksPlatform::getTemplateService();
   		
   		// Time
   		$_SESSION['timezone'] = $timezone;
   		@date_default_timezone_set($timezone);
   		DAO_WorkerPref::set($worker->id,'timezone',$timezone);
   		
   		// Language
   		$_SESSION['locale'] = $lang_code;
   		DevblocksPlatform::setLocale($lang_code);
   		DAO_WorkerPref::set($worker->id,'locale',$lang_code);
   		
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

		@$assist_mode = DevblocksPlatform::importGPC($_REQUEST['assist_mode'],'integer',0);
		DAO_WorkerPref::set($worker->id, 'assist_mode', $assist_mode);

		@$keyboard_shortcuts = DevblocksPlatform::importGPC($_REQUEST['keyboard_shortcuts'],'integer',0);
		DAO_WorkerPref::set($worker->id, 'keyboard_shortcuts', $keyboard_shortcuts);

		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('preferences')));
	}
	
	// Ajax [TODO] This should probably turn into Extension_PreferenceTab
	function showTabAlertsAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = $this->_TPL_PATH;
		$tpl->assign('path', $tpl_path);
		
		$active_worker = PortSensorApplication::getActiveWorker();
//		$tpl->assign('worker', $worker);

		// View
		$alertsView = Ps_AbstractViewLoader::getView(self::VIEW_MY_ALERTS);
		
		if(null == $alertsView) {
			$alertsView = new Ps_AlertView();
			$alertsView->id = self::VIEW_MY_ALERTS;
			$alertsView->name = $translate->_('preferences.tab.alerts');
			$alertsView->renderLimit = 25;
			$alertsView->renderPage = 0;
			$alertsView->renderSortBy = SearchFields_Alert::POS;
			$alertsView->renderSortAsc = 0;
			$alertsView->params = array();
		}
		
		$alertsView->name = 'Alerts: ' . $active_worker->getName();
		$alertsView->params = array(
			SearchFields_Alert::IS_DISABLED => new DevblocksSearchCriteria(SearchFields_Alert::IS_DISABLED,'=',0),		
			SearchFields_Alert::WORKER_ID => new DevblocksSearchCriteria(SearchFields_Alert::WORKER_ID,'=',$active_worker->id),		
		);

		Ps_AbstractViewLoader::setView($alertsView->id, $alertsView);		
		
		//$tpl->assign('response_uri', 'preferences/alerts');

//		$quick_search_type = $visit->get('crm.opps.quick_search_type');
//		$tpl->assign('quick_search_type', $quick_search_type);
		
		$tpl->assign('view', $alertsView);
		$tpl->assign('view_fields', Ps_AlertView::getFields());
		$tpl->assign('view_searchable_fields', Ps_AlertView::getSearchFields());

		$tpl->display('file:' . $tpl_path . 'preferences/tabs/alerts.tpl');
	}
	
	// Ajax
	function showAlertPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		$tpl->assign('view_id', $view_id);

		$active_worker = PortSensorApplication::getActiveWorker();
		
		if(null != ($alert = DAO_Alert::get($id))) {
			$tpl->assign('alert', $alert);
		}
		
		if(null == (@$worker_id = $alert->worker_id)) {
			$worker_id = $active_worker->id;
		}
		
		$sensor_type_mfts = DevblocksPlatform::getExtensions('portsensor.sensor', false);
		$tpl->assign('sensor_type_mfts', $sensor_type_mfts);
		
		$tpl->assign('workers', DAO_Worker::getAllActive());
		$tpl->assign('all_workers', DAO_Worker::getAll());

		// Custom Fields: Sensor
		$sensor_fields = DAO_CustomField::getBySource(PsCustomFieldSource_Sensor::ID);
		$tpl->assign('sensor_fields', $sensor_fields);
		
		// Criteria extensions
		$alert_criteria_exts = DevblocksPlatform::getExtensions('portsensor.alert.criteria', true);
		$tpl->assign('alert_criteria_exts', $alert_criteria_exts);
		
		// Action extensions
		$alert_action_exts = DevblocksPlatform::getExtensions('portsensor.alert.action', true);
		$tpl->assign('alert_action_exts', $alert_action_exts);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'alerts/peek.tpl');
	}
	
	function saveAlertPeekAction() {
   		$translate = DevblocksPlatform::getTranslationService();
   		
   		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
   		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
   		
	    @$active_worker = PortSensorApplication::getActiveWorker();

	    /*****************************/
		@$name = DevblocksPlatform::importGPC($_POST['name'],'string','');
		@$is_disabled = DevblocksPlatform::importGPC($_POST['is_disabled'],'integer',0);
		@$worker_id = DevblocksPlatform::importGPC($_POST['worker_id'],'integer',0);
		@$rules = DevblocksPlatform::importGPC($_POST['rules'],'array',array());
		@$do = DevblocksPlatform::importGPC($_POST['do'],'array',array());
		
		@$delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);
		
		if(!empty($id) && !empty($delete)) {
			DAO_Alert::delete($id);
			
		} else {
			if(empty($name))
				$name = $translate->_('Alert');
		
			$criterion = array();
			$actions = array();
			
			// Custom fields
			$custom_fields = DAO_CustomField::getAll();
			
			$alert_criteria_exts = DevblocksPlatform::getExtensions('portsensor.alert.criteria', false);
			
			// Criteria
			if(is_array($rules))
			foreach($rules as $rule) {
				$rule = DevblocksPlatform::strAlphaNumDash($rule);
				@$value = DevblocksPlatform::importGPC($_POST['value_'.$rule],'string','');
				
				// [JAS]: Allow empty $value (null/blank checking)
				
				$criteria = array(
					'value' => $value,
				);
				
				// Any special rule handling
				switch($rule) {
					case 'dayofweek':
						// days
						$days = DevblocksPlatform::importGPC($_REQUEST['value_dayofweek'],'array',array());
						if(in_array(0,$days)) $criteria['sun'] = 'Sunday';
						if(in_array(1,$days)) $criteria['mon'] = 'Monday';
						if(in_array(2,$days)) $criteria['tue'] = 'Tuesday';
						if(in_array(3,$days)) $criteria['wed'] = 'Wednesday';
						if(in_array(4,$days)) $criteria['thu'] = 'Thursday';
						if(in_array(5,$days)) $criteria['fri'] = 'Friday';
						if(in_array(6,$days)) $criteria['sat'] = 'Saturday';
						unset($criteria['value']);
						break;
					case 'timeofday':
						$from = DevblocksPlatform::importGPC($_REQUEST['timeofday_from'],'string','');
						$to = DevblocksPlatform::importGPC($_REQUEST['timeofday_to'],'string','');
						$criteria['from'] = $from;
						$criteria['to'] = $to;
						unset($criteria['value']);
						break;
					case 'event':
						@$events = DevblocksPlatform::importGPC($_REQUEST['value_event'],'array',array());
						if(is_array($events))
						foreach($events as $event)
							$criteria[$event] = true;
						unset($criteria['value']);
						break;
					case 'alert_last_ran':
						@$from = DevblocksPlatform::importGPC($_REQUEST['value_alert_last_ran_from'],'string','');
						@$to = DevblocksPlatform::importGPC($_REQUEST['value_alert_last_ran_to'],'string','');
						$criteria['from'] = $from;
						$criteria['to'] = $to;
						unset($criteria['value']);
						break;
					case 'sensor_name':
						break;
					case 'sensor_fail_count':
						$oper = DevblocksPlatform::importGPC($_REQUEST['oper_sensor_fail_count'],'string','=');
						$criteria['oper'] = $oper;
						break;
					case 'sensor_type':
						@$types = DevblocksPlatform::importGPC($_REQUEST['value_sensor_types'],'array',array());
						if(is_array($types))
						foreach($types as $type)
							$criteria[$type] = true;
						unset($criteria['value']);
						break;
					default: // ignore invalids // [TODO] Very redundant
						// Custom fields
						if("cf_" == substr($rule,0,3)) {
							$field_id = intval(substr($rule,3));
							
							if(!isset($custom_fields[$field_id]))
								continue;
	
							// [TODO] Operators
								
							switch($custom_fields[$field_id]->type) {
								case 'S': // string
								case 'T': // clob
								case 'U': // URL
									@$oper = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_oper'],'string','regexp');
									$criteria['oper'] = $oper;
									break;
								case 'D': // dropdown
								case 'M': // multi-dropdown
								case 'X': // multi-checkbox
								case 'W': // worker
									@$in_array = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id],'array',array());
									$out_array = array();
									
									// Hash key on the option for quick lookup later
									if(is_array($in_array))
									foreach($in_array as $k => $v) {
										$out_array[$v] = $v;
									}
									
									$criteria['value'] = $out_array;
									break;
								case 'E': // date
									@$from = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_from'],'string','0');
									@$to = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_to'],'string','now');
									$criteria['from'] = $from;
									$criteria['to'] = $to;
									unset($criteria['value']);
									break;
								case 'N': // number
									@$oper = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_oper'],'string','=');
									$criteria['oper'] = $oper;
									$criteria['value'] = intval($value);
									break;
								case 'C': // checkbox
									$criteria['value'] = intval($value);
									break;
							}
							
						} elseif(isset($alert_criteria_exts[$rule])) { // Extensions
							// Save custom criteria properties
							try {
								$crit_ext = $alert_criteria_exts[$rule]->createInstance();
								/* @var $crit_ext Extension_AlertCriteria */
								$criteria = $crit_ext->saveConfig();
							} catch(Exception $e) {
								// print_r($e);
							}
						} else {
							continue;
						}
						
						break;
				}
				
				$criterion[$rule] = $criteria;
			}
			
			$alert_action_exts = DevblocksPlatform::getExtensions('portsensor.alert.action', false);
			
			// Actions
			if(is_array($do))
			foreach($do as $act) {
				$action = array();
				
				switch($act) {
					// Forward a copy to...
					case 'email':
						@$emails = DevblocksPlatform::importGPC($_REQUEST['do_email'],'array',array());
						if(!empty($emails)) {
							$action = array(
								'to' => $emails
							);
						}
						break;
						
					// Watcher notification
					case 'notify':
						//@$emails = DevblocksPlatform::importGPC($_REQUEST['do_email'],'array',array());
						//if(!empty($emails)) {
							$action = array(
								//'to' => $emails
							);
						//}
						break;
					default: // ignore invalids
						// Custom fields
						if("cf_" == substr($act,0,3)) {
							$field_id = intval(substr($act,3));
							
							if(!isset($custom_fields[$field_id]))
								continue;
	
							$action = array();
								
							switch($custom_fields[$field_id]->type) {
								case 'S': // string
								case 'T': // clob
								case 'U': // URL
								case 'D': // dropdown
								case 'W': // worker
									$value = DevblocksPlatform::importGPC($_REQUEST['do_cf_'.$field_id],'string','');
									$action['value'] = $value;
									break;
								case 'M': // multi-dropdown
								case 'X': // multi-checkbox
									$in_array = DevblocksPlatform::importGPC($_REQUEST['do_cf_'.$field_id],'array',array());
									$out_array = array();
									
									// Hash key on the option for quick lookup later
									if(is_array($in_array))
									foreach($in_array as $k => $v) {
										$out_array[$v] = $v;
									}
									
									$action['value'] = $out_array;
									break;
								case 'E': // date
									$value = DevblocksPlatform::importGPC($_REQUEST['do_cf_'.$field_id],'string','');
									$action['value'] = $value;
									break;
								case 'N': // number
								case 'C': // checkbox
									$value = DevblocksPlatform::importGPC($_REQUEST['do_cf_'.$field_id],'string','');
									$action['value'] = intval($value);
									break;
							}
							
						} elseif(isset($alert_action_exts[$act])) {
							// Save custom action properties
							try {
								$action_ext = $alert_action_exts[$act]->createInstance();
								$action = $action_ext->saveConfig();
								
							} catch(Exception $e) {
								// print_r($e);
							}
						} else {
							continue;
						}
						break;					
				}
				
				$actions[$act] = $action;
			}
	
	   		$fields = array(
	   			DAO_Alert::NAME => $name,
	   			DAO_Alert::IS_DISABLED => $is_disabled,
	   			DAO_Alert::WORKER_ID => $worker_id,
	   			DAO_Alert::CRITERIA_JSON => json_encode($criterion),
	   			DAO_Alert::ACTIONS_JSON => json_encode($actions),
	   		);
	
	   		// Create
	   		if(empty($id)) {
	   			$fields[DAO_Alert::POS] = 0;
		   		$id = DAO_Alert::create($fields);
		   		
		   	// Update
	   		} else {
	   			DAO_Alert::update($id, $fields);
	   		}			
		}
		
		if(!empty($view_id)) {
			$view = Ps_AbstractViewLoader::getView($view_id);
			$view->render();
		}
   		
	}
	
};

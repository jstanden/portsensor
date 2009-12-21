<?php
class PsSensorsPage extends PortSensorPageExtension {
	private $_TPL_PATH = '';
	
	const VIEW_ALL_SENSORS = 'sensors_all';
	
	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
		parent::__construct($manifest);
	}
		
	function isVisible() {
		// check login
		$visit = PortSensorApplication::getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}

	function getActivity() {
		return new Model_Activity('activity.sensors');
	}
	
	function render() {
		$active_worker = PortSensorApplication::getActiveWorker();
		$visit = PortSensorApplication::getVisit();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);

		$response = DevblocksPlatform::getHttpResponse();
		$tpl->assign('request_path', implode('/',$response->path));
		
		// Remember the last tab/URL
//		if(null == ($selected_tab = @$response->path[1])) {
//			$selected_tab = $visit->get(PortSensorVisit::KEY_HOME_SELECTED_TAB, 'notifications');
//		}
//		$tpl->assign('selected_tab', $selected_tab);
		
		$tab_manifests = DevblocksPlatform::getExtensions('portsensor.sensors.tab', false);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'sensors/index.tpl');
	}
	
	// Ajax
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_SensorsTab) {
			$inst->showTab();
		}
	}
	
	// Post
	function saveTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_SensorsTab) {
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
			&& $inst instanceof Extension_SensorsTab) {
				if(method_exists($inst,$action.'Action')) {
					call_user_func(array(&$inst, $action.'Action'));
				}
		}
	}
	
	function showTabAllSensorsAction() {
		$visit = PortSensorApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = PortSensorApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		// Select tab
//		$visit->set(PortSensorVisit::KEY_HOME_SELECTED_TAB, 'sensors');
		
		// My Notifications
		$sensorsView = Ps_AbstractViewLoader::getView(self::VIEW_ALL_SENSORS);
		
//		$title = vsprintf($translate->_('home.my_notifications.view.title'), $active_worker->getName());
		
		if(null == $sensorsView) {
			$sensorsView = new Ps_SensorView();
			$sensorsView->id = self::VIEW_ALL_SENSORS;
//			$sensorsView->name = $title;
			$sensorsView->name = $translate->_('sensors.tab.all_sensors');
			$sensorsView->renderLimit = 25;
			$sensorsView->renderPage = 0;
			$sensorsView->renderSortBy = SearchFields_Sensor::NAME;
			$sensorsView->renderSortAsc = 1;
			$sensorsView->params = array(
				SearchFields_Sensor::IS_DISABLED => new DevblocksSearchCriteria(SearchFields_Sensor::IS_DISABLED,'=',0),
			);
		
			Ps_AbstractViewLoader::setView($sensorsView->id, $sensorsView);
		}
		
		/*
		 * [TODO] This doesn't need to save every display, but it was possible to 
		 * lose the params in the saved version of the view in the DB w/o recovery.
		 * This should be moved back into the if(null==...) check in a later build.
		 */
		
		$tpl->assign('response_uri', 'sensors/all');

		// *** NEW
//		$defaults = new Ps_AbstractViewModel();
//		$defaults->id = self::VIEW_ALL_SENSORS;
//		$defaults->class_name = 'Ps_SensorView';
//		
//		$view = Ps_AbstractViewLoader::getView($defaults->id, $defaults);
//		$tpl->assign('view', $view);
//		$tpl->assign('view_fields', Ps_WorkerView::getFields());
//		$tpl->assign('view_searchable_fields', Ps_WorkerView::getSearchFields());
		// ** NEW END
		
//		$quick_search_type = $visit->get('crm.opps.quick_search_type');
//		$tpl->assign('quick_search_type', $quick_search_type);
		
		$tpl->assign('view', $sensorsView);
		$tpl->assign('view_fields', Ps_SensorView::getFields());
		$tpl->assign('view_searchable_fields', Ps_SensorView::getSearchFields());
		
		$tpl->display('file:' . $this->_TPL_PATH . 'sensors/tabs/all/index.tpl');
	}
	
	function showSensorPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);
		
		$tpl->assign('view_id', $view_id);
		
		$sensor = DAO_Sensor::get($id);
		$tpl->assign('sensor', $sensor);
		
		$sensor_types = DevblocksPlatform::getExtensions('portsensor.sensor',false);
		$tpl->assign('sensor_types', $sensor_types);
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getBySource(PsCustomFieldSource_Sensor::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		$custom_field_values = DAO_CustomFieldValue::getValuesBySourceIds(PsCustomFieldSource_Sensor::ID, $id);
		if(isset($custom_field_values[$id]))
			$tpl->assign('custom_field_values', $custom_field_values[$id]);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'sensors/peek.tpl');		
	}
	
	function saveSensorPeekAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = PortSensorApplication::getActiveWorker();
		
		// [TODO] ACL
		// return;
		
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer');
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		@$name= DevblocksPlatform::importGPC($_POST['name'],'string');
		@$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'],'string');
		@$metric_type = DevblocksPlatform::importGPC($_POST['metric_type'],'string');
		@$disabled = DevblocksPlatform::importGPC($_POST['is_disabled'],'integer',0);
		@$delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);

		// [TODO] The superuser set bit here needs to be protected by ACL
		
		if(empty($name)) $name = "New Sensor";
		
		if(!empty($id) && !empty($delete)) {
			DAO_Sensor::delete($id);
			
		} else {
		    $fields = array(
		    	DAO_Sensor::NAME => $name,
		    	DAO_Sensor::EXTENSION_ID => $extension_id,
		    	DAO_Sensor::IS_DISABLED => $disabled,
		    );

		    if(empty($extension_id)) {
		    	// Manual sensor requires an explicit metric type
		    	$fields[DAO_Sensor::METRIC_TYPE] = $metric_type;
		    } else {
		    	$sensor_type = DevblocksPlatform::getExtension($extension_id,false);
		    	// [TODO] Sensor extension provides metric type
		    	$fields[DAO_Sensor::METRIC_TYPE] = $sensor_type->params['metric_type'];
		    }
		    
			if(empty($id))
				$id = DAO_Sensor::create($fields);
			else
				DAO_Sensor::update($id, $fields);
			
			// Custom field saves
			@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost(PsCustomFieldSource_Sensor::ID, $id, $field_ids);
		}
		
		if(!empty($view_id)) {
			$view = Ps_AbstractViewLoader::getView($view_id);
			$view->render();
		}
		
		//DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','workers')));		
	}	
	
};

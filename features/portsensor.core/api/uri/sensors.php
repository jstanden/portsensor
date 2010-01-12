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
		$translate = DevblocksPlatform::getTranslationService();
		$visit = PortSensorApplication::getVisit();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);

		$response = DevblocksPlatform::getHttpResponse();
		$tpl->assign('request_path', implode('/',$response->path));

		// View
		$sensorsView = Ps_AbstractViewLoader::getView(self::VIEW_ALL_SENSORS);
		
		if(null == $sensorsView) {
			$sensorsView = new Ps_SensorView();
			$sensorsView->id = self::VIEW_ALL_SENSORS;
			$sensorsView->name = $translate->_('core.menu.sensors');
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
		
		$tpl->assign('response_uri', 'sensors');

//		$quick_search_type = $visit->get('crm.opps.quick_search_type');
//		$tpl->assign('quick_search_type', $quick_search_type);
		
		$tpl->assign('view', $sensorsView);
		$tpl->assign('view_fields', Ps_SensorView::getFields());
		$tpl->assign('view_searchable_fields', Ps_SensorView::getSearchFields());
		
		$tpl->display('file:' . $this->_TPL_PATH . 'sensors/index.tpl');
	}
	
	function showSensorPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);
		
		$tpl->assign('view_id', $view_id);
		
		if(null == ($sensor = DAO_Sensor::get($id))) {
			$sensor = new Model_Sensor();
			$sensor->extension_id = 'sensor.external';
		}
		$tpl->assign('sensor', $sensor);
		
		$sensor_types = DevblocksPlatform::getExtensions('portsensor.sensor', false);
		$tpl->assign('sensor_types', $sensor_types);
		
		// Sensor extension instance
		if(!empty($sensor->extension_id) && isset($sensor_types[$sensor->extension_id])) {
			$tpl->assign('sensor_extension', DevblocksPlatform::getExtension($sensor->extension_id,true));
		}
		
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

			if(empty($id))
				$id = DAO_Sensor::create($fields);
			else
				DAO_Sensor::update($id, $fields);
			
			// Save sensor extension config
			if(!empty($extension_id)) {
				if(null != ($ext = DevblocksPlatform::getExtension($extension_id, true))) {
					if(null != ($sensor = DAO_Sensor::get($id))
					 && $ext instanceof Extension_Sensor) {
						$ext->saveConfig($sensor);
					}
				}
			}
				
			// Custom field saves
			@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost(PsCustomFieldSource_Sensor::ID, $id, $field_ids);
		}
		
		if(!empty($view_id)) {
			$view = Ps_AbstractViewLoader::getView($view_id);
			$view->render();
		}
	}	
	
	function showSensorExtensionConfigAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		if(null == ($sensor = DAO_Sensor::get($id)))
			$sensor = new Model_Sensor();
		
		if(!empty($ext_id)) {
			if(null != ($ext = DevblocksPlatform::getExtension($ext_id, true))) {
				if($ext instanceof Extension_Sensor) {
					$ext->renderConfig($sensor);
				}
			}
		}
		
	}
	
	function viewRunNowAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
	    @$ids = DevblocksPlatform::importGPC($_REQUEST['row_id'],'array');
	    
	    $sensor_types = DevblocksPlatform::getExtensions('portsensor.sensor', true);

	    if(is_array($ids) && !empty($ids)) {
		    try {
		    	$sensors = DAO_Sensor::getWhere(sprintf("%s IN (%s)", DAO_Sensor::ID, implode(',', $ids)));
		    	
		    	if(is_array($sensors))
		    	foreach($sensors as $sensor) {
	    			if(isset($sensor_types[$sensor->extension_id])) {
	    				// Skip external sensors
	    				if('sensor.external' == $sensor->extension_id)
	    					continue;
	    				
	    				$runner = $sensor_types[$sensor->extension_id];
	    				
	    				// [TODO] This duplicates cron
						if(method_exists($runner,'run')) {
							$fields = array();
							$success = $runner->run($sensor, $fields);
							
							$fields[DAO_Sensor::UPDATED_DATE] = time();
							$fields[DAO_Sensor::FAIL_COUNT] = ($success ? 0 : (intval($sensor->fail_count)+1));
							
							DAO_Sensor::update($sensor->id, $fields);
						}
	    			}
		    	}
		    	
		    } catch(Exception $e) {
		    	// ...
		    }
	    }
	    
	    $view = Ps_AbstractViewLoader::getView($view_id);
	    $view->render();
	    return;
	}	
	
};

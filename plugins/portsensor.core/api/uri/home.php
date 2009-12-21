<?php
class PsHomePage extends PortSensorPageExtension {
	private $_TPL_PATH = '';
	
	const VIEW_MY_NOTIFICATIONS = 'home_my_notifications';
	const VIEW_ACTIVE_SENSORS = 'home_sensors';
	
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
		return new Model_Activity('activity.home');
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
		if(null == ($selected_tab = @$response->path[1])) {
			$selected_tab = $visit->get(PortSensorVisit::KEY_HOME_SELECTED_TAB, 'notifications');
		}
		$tpl->assign('selected_tab', $selected_tab);
		
		$tab_manifests = DevblocksPlatform::getExtensions('portsensor.home.tab', false);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Custom workspaces
//		$workspaces = DAO_WorkerWorkspaceList::getWorkspaces($active_worker->id);
//		$tpl->assign('workspaces', $workspaces);
		
		// ====== Who's Online
		$whos_online = DAO_Worker::getAllOnline();
		if(!empty($whos_online)) {
			$tpl->assign('whos_online', $whos_online);
			$tpl->assign('whos_online_count', count($whos_online));
		}
		
		$tpl->display('file:' . $this->_TPL_PATH . 'home/index.tpl');
	}
	
	// Ajax
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_HomeTab) {
			$inst->showTab();
		}
	}
	
	// Post
	function saveTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_HomeTab) {
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
			&& $inst instanceof Extension_HomeTab) {
				if(method_exists($inst,$action.'Action')) {
					call_user_func(array(&$inst, $action.'Action'));
				}
		}
	}
	
	function showTabNotificationsAction() {
		$visit = PortSensorApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = PortSensorApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		// Select tab
		$visit->set(PortSensorVisit::KEY_HOME_SELECTED_TAB, 'notifications');
		
		// My Notifications
		$myNotificationsView = Ps_AbstractViewLoader::getView(self::VIEW_MY_NOTIFICATIONS);
		
		$title = vsprintf($translate->_('home.my_notifications.view.title'), $active_worker->getName());
		
		if(null == $myNotificationsView) {
			$myNotificationsView = new Ps_WorkerEventView();
			$myNotificationsView->id = self::VIEW_MY_NOTIFICATIONS;
			$myNotificationsView->name = $title;
			$myNotificationsView->renderLimit = 25;
			$myNotificationsView->renderPage = 0;
			$myNotificationsView->renderSortBy = SearchFields_WorkerEvent::CREATED_DATE;
			$myNotificationsView->renderSortAsc = 0;
		}

		// Overload criteria
		$myNotificationsView->name = $title;
		$myNotificationsView->params = array(
			SearchFields_WorkerEvent::WORKER_ID => new DevblocksSearchCriteria(SearchFields_WorkerEvent::WORKER_ID,'=',$active_worker->id),
			SearchFields_WorkerEvent::IS_READ => new DevblocksSearchCriteria(SearchFields_WorkerEvent::IS_READ,'=',0),
		);
		/*
		 * [TODO] This doesn't need to save every display, but it was possible to 
		 * lose the params in the saved version of the view in the DB w/o recovery.
		 * This should be moved back into the if(null==...) check in a later build.
		 */
		Ps_AbstractViewLoader::setView($myNotificationsView->id,$myNotificationsView);
		
		$tpl->assign('view', $myNotificationsView);
		$tpl->display('file:' . $this->_TPL_PATH . 'home/tabs/my_notifications/index.tpl');
	}	
	
	/**
	 * Open an event, mark it read, and redirect to its URL.
	 * Used by Home->Notifications view.
	 *
	 */
	function redirectReadAction() {
		$worker = PortSensorApplication::getActiveWorker();
		
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		
		array_shift($stack); // home
		array_shift($stack); // redirectReadAction
		@$id = array_shift($stack); // id
		
		if(null != ($event = DAO_WorkerEvent::get($id))) {
			// Mark as read before we redirect
			DAO_WorkerEvent::update($id, array(
				DAO_WorkerEvent::IS_READ => 1
			));
			
			DAO_WorkerEvent::clearCountCache($worker->id);

			session_write_close();
			header("Location: " . $event->url);
		}
		exit;
	} 
	
	function doNotificationsMarkReadAction() {
		$worker = PortSensorApplication::getActiveWorker();
		
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		@$row_ids = DevblocksPlatform::importGPC($_REQUEST['row_id'],'array',array());

		if(is_array($row_ids) && !empty($row_ids)) {
			DAO_WorkerEvent::updateWhere(
				array(
					DAO_WorkerEvent::IS_READ => 1,
				), 
				sprintf("%s = %d AND %s IN (%s)",
					DAO_WorkerEvent::WORKER_ID,
					$worker->id,
					DAO_WorkerEvent::ID,
					implode(',', $row_ids)
				)
			);
			
			DAO_WorkerEvent::clearCountCache($worker->id);
		}
		
		$myEventsView = Ps_AbstractViewLoader::getView($view_id);
		$myEventsView->render();
	}

	function showTabSensorsAction() {
		$visit = PortSensorApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = PortSensorApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		// Select tab
//		$visit->set(PortSensorVisit::KEY_HOME_SELECTED_TAB, 'sensors');
		
		// My Notifications
		$sensorsView = Ps_AbstractViewLoader::getView(self::VIEW_ACTIVE_SENSORS);
		
//		$title = vsprintf($translate->_('home.my_notifications.view.title'), $active_worker->getName());
		
		if(null == $sensorsView) {
			$sensorsView = new Ps_SensorView();
			$sensorsView->id = self::VIEW_ACTIVE_SENSORS;
//			$sensorsView->name = $title;
			$sensorsView->renderLimit = 25;
			$sensorsView->renderPage = 0;
			$sensorsView->renderSortBy = SearchFields_Sensor::NAME;
			$sensorsView->renderSortAsc = 1;
		}

		// Overload criteria
		$sensorsView->name = 'Active Sensors';
		$sensorsView->params = array(
			SearchFields_Sensor::IS_DISABLED => new DevblocksSearchCriteria(SearchFields_Sensor::IS_DISABLED,'=',0),
		);
		
		/*
		 * [TODO] This doesn't need to save every display, but it was possible to 
		 * lose the params in the saved version of the view in the DB w/o recovery.
		 * This should be moved back into the if(null==...) check in a later build.
		 */
		Ps_AbstractViewLoader::setView($sensorsView->id, $sensorsView);
		
		$tpl->assign('view', $sensorsView);
		$tpl->display('file:' . $this->_TPL_PATH . 'home/tabs/sensors/index.tpl');
	}
	
};

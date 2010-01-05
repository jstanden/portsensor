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
		$workspaces = DAO_Worklist::getWorkspaces($active_worker->id);
		$tpl->assign('workspaces', $workspaces);
		
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
	
	function showAddWorkspacePanelAction() {
		$active_worker = PortSensorApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);

		$source_manifests = DevblocksPlatform::getExtensions(Extension_WorklistSource::EXTENSION_POINT, false);
		uasort($source_manifests, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
		$tpl->assign('sources', $source_manifests);		
		
		$workspaces = DAO_Worklist::getWorkspaces($active_worker->id);
		$tpl->assign('workspaces', $workspaces);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'home/workspaces/add_workspace_panel.tpl');
	}
	
	function doAddWorkspaceAction() {
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
		@$source = DevblocksPlatform::importGPC($_REQUEST['source'], 'string', '');
		@$workspace = DevblocksPlatform::importGPC($_REQUEST['workspace'], 'string', '');
		@$new_workspace = DevblocksPlatform::importGPC($_REQUEST['new_workspace'], 'string', '');
		
		$active_worker = PortSensorApplication::getActiveWorker();
		$visit = PortSensorApplication::getVisit();

		// Source extension exists
		if(null != ($source_manifest = DevblocksPlatform::getExtension($source, false))) {
			
			// Class exists
			if(null != (@$class = $source_manifest->params['view_class'])) {

				if(empty($name))
					$name = $source_manifest->name;
				
				// New workspace
				if(!empty($new_workspace))
					$workspace = $new_workspace;
					
				if(empty($workspace))
					$workspace = 'New Workspace';
					
				$view = new $class; /* @var $view Ps_AbstractView */ 
					
				// Build the list model
				$list = new Model_WorklistView();
				$list->title = $name;
				$list->columns = $view->view_columns;
				$list->params = $view->params;
				$list->num_rows = $view->renderLimit;
				$list->sort_by = $view->renderSortBy;
				$list->sort_asc = $view->renderSortAsc;
				
				// Add the worklist
				$fields = array(
					DAO_Worklist::WORKER_ID => $active_worker->id,
					DAO_Worklist::VIEW_POS => 1,
					DAO_Worklist::VIEW_SERIALIZED => serialize($list),
					DAO_Worklist::WORKSPACE => $workspace,
					DAO_Worklist::SOURCE_EXTENSION => $source_manifest->id,
				);
				DAO_Worklist::create($fields);
				
				// Select the new tab
				$visit->set(PortSensorVisit::KEY_HOME_SELECTED_TAB, 'w_'.$workspace);
			}
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('home')));
	}	
	
	function showWorkspaceTabAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$visit = PortSensorApplication::getVisit();
		$db = DevblocksPlatform::getDatabaseService();
		$active_worker = PortSensorApplication::getActiveWorker();
		
		$current_workspace = DevblocksPlatform::importGPC($_REQUEST['workspace'],'string','');
		$workspaces = DAO_Worklist::getWorkspaces($active_worker->id);

		// Fix a bad/old cache
		if(!empty($current_workspace) && false === array_search($current_workspace,$workspaces))
			$current_workspace = '';
		
		$views = array();
			
		if(empty($current_workspace) && !empty($workspaces)) { // custom dashboards
			$current_workspace = reset($workspaces);
		}
		
		if(!empty($current_workspace)) {
			// Remember the tab
			$visit->set(PortSensorVisit::KEY_HOME_SELECTED_TAB, 'w_'.$current_workspace);
			
			$lists = DAO_Worklist::getWhere(sprintf("%s = %d AND %s = %s",
				DAO_Worklist::WORKER_ID,
				$active_worker->id,
				DAO_Worklist::WORKSPACE,
				$db->qstr($current_workspace)
			));

			// Load the workspace sources to map to view renderer
	        $source_manifests = DevblocksPlatform::getExtensions(Extension_WorklistSource::EXTENSION_POINT, false);

	        // Loop through list schemas
			if(is_array($lists) && !empty($lists))
			foreach($lists as $list) { /* @var $list Model_Worklist */
				$view_id = 'cust_'.$list->id;
				if(null == ($view = Ps_AbstractViewLoader::getView($view_id))) {
					$list_view = $list->view;
					
					// Make sure we can find the workspace source (plugin not disabled)
					if(!isset($source_manifests[$list->source_extension])
						|| null == ($workspace_source = $source_manifests[$list->source_extension])
						|| !isset($workspace_source->params['view_class']))
						continue;
					
					// Make sure our workspace source has a valid renderer class
					$view_class = $workspace_source->params['view_class'];
					if(!class_exists($view_class))
						continue;
					
					$view = new $view_class;
					$view->id = $view_id;
					$view->name = $list_view->title;
					$view->renderLimit = $list_view->num_rows;
					$view->renderPage = 0;
					$view->view_columns = $list_view->columns;
					$view->params = $list_view->params;
					$view->renderSortBy = $list_view->sort_by;
					$view->renderSortAsc = $list_view->sort_asc;
					Ps_AbstractViewLoader::setView($view_id, $view);
				}
				
				if(!empty($view))
					$views[] = $view;
			}
		
			$tpl->assign('current_workspace', $current_workspace);
			$tpl->assign('views', $views);
		}
		
		// Log activity
		DAO_Worker::logActivity(
			$active_worker->id,
			new Model_Activity(
				'activity.mail.workspaces',
				array(
					'<i>'.$current_workspace.'</i>'
				)
			)
		);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'home/workspaces/index.tpl');
	}
	
	function showEditWorkspacePanelAction() {
		@$workspace = DevblocksPlatform::importGPC($_REQUEST['workspace'],'string', '');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = $this->_TPL_PATH;
		$tpl->assign('path', $tpl_path);

		$db = DevblocksPlatform::getDatabaseService();
		
		$active_worker = PortSensorApplication::getActiveWorker();

		$tpl->assign('workspace', $workspace);
		
		$worklists = DAO_Worklist::getWhere(sprintf("%s = %s AND %s = %d",
			DAO_Worklist::WORKSPACE,
			$db->qstr($workspace),
			DAO_Worklist::WORKER_ID,
			$active_worker->id
		));
		$tpl->assign('worklists', $worklists);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'home/workspaces/edit_workspace_panel.tpl');
	}
	
	function doEditWorkspaceAction() {
		@$workspace = DevblocksPlatform::importGPC($_POST['workspace'],'string', '');
		@$rename_workspace = DevblocksPlatform::importGPC($_POST['rename_workspace'],'string', '');
		@$ids = DevblocksPlatform::importGPC($_POST['ids'],'array', array());
		@$names = DevblocksPlatform::importGPC($_POST['names'],'array', array());
		@$pos = DevblocksPlatform::importGPC($_POST['pos'],'array', array());
		@$deletes = DevblocksPlatform::importGPC($_POST['deletes'],'array', array());

		$db = DevblocksPlatform::getDatabaseService();
		$active_worker = PortSensorApplication::getActiveWorker();
		$visit = PortSensorApplication::getVisit();
		
		$worklists = DAO_Worklist::getWhere(sprintf("%s = %s",
			DAO_Worklist::WORKSPACE,
			$db->qstr($workspace)
		));
		
		// Reorder worklists, rename lists, delete lists, on workspace
		if(is_array($ids) && !empty($ids))
		foreach($ids as $idx => $id) {
			if(false !== array_search($id, $deletes)) {
				DAO_Worklist::delete($id);
				Ps_AbstractViewLoader::deleteView('cust_'.$id); // free up a little memory
				
			} else {
				if(!isset($worklists[$id]))
					continue;
					
				$list_view = $worklists[$id]->view;
				
				// If the name changed
				if(isset($names[$idx]) && 0 != strcmp($list_view->title,$names[$idx])) {
					$list_view->title = $names[$idx];
				
					// Save the view in the session
					$view = Ps_AbstractViewLoader::getView('cust_'.$id);
					$view->name = $list_view->title;
					Ps_AbstractViewLoader::setView('cust_'.$id, $view);
				}
					
				DAO_Worklist::update($id,array(
					DAO_Worklist::VIEW_POS => @intval($pos[$idx]),
					DAO_Worklist::VIEW_SERIALIZED => serialize($list_view),
				));
			}
		}

		// Rename workspace
		if(!empty($rename_workspace)) {
			$fields = array(
				DAO_Worklist::WORKSPACE => $rename_workspace,
			);
			DAO_Worklist::updateWhere($fields, sprintf("%s = %s AND %s = %d",
				DAO_Worklist::WORKSPACE,
				$db->qstr($workspace),
				DAO_Worklist::WORKER_ID,
				$active_worker->id
			));
			
			$workspace = $rename_workspace;
		}
		
		// Change active tab
		$visit->set(PortSensorVisit::KEY_HOME_SELECTED_TAB, 'w_'.$workspace);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('home')));	
	}
	
	function doDeleteWorkspaceAction() {
		@$workspace = DevblocksPlatform::importGPC($_POST['workspace'],'string', '');
		
		$db = DevblocksPlatform::getDatabaseService();
		$active_worker = PortSensorApplication::getActiveWorker();

		$lists = DAO_Worklist::getWhere(sprintf("%s = %s AND %s = %d",
			DAO_Worklist::WORKSPACE,
			$db->qstr($workspace),
			DAO_Worklist::WORKER_ID,
			$active_worker->id
		));

		DAO_Worklist::delete(array_keys($lists));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('home')));	
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

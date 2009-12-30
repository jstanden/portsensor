<?php
class PsInternalController extends DevblocksControllerExtension {
	const ID = 'core.controller.internal';
	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
		parent::__construct($manifest);
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$worker = PortSensorApplication::getActiveWorker();
		if(empty($worker)) return;
		
		$stack = $request->path;
		array_shift($stack); // internal
		
	    @$action = array_shift($stack) . 'Action';

	    switch($action) {
	        case NULL:
	            // [TODO] Index/page render
	            break;
	            
	        default:
			    // Default action, call arg as a method suffixed with Action
				if(method_exists($this,$action)) {
					call_user_func(array(&$this, $action));
				}
	            break;
	    }
	}
	
	// Ajax
	function viewRefreshAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		$view = Ps_AbstractViewLoader::getView($id);
		$view->render();
	}
	
	function viewSortByAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$sortBy = DevblocksPlatform::importGPC($_REQUEST['sortBy']);
		
		$view = Ps_AbstractViewLoader::getView($id);
		$view->doSortBy($sortBy);
		Ps_AbstractViewLoader::setView($id, $view);
		
		$view->render();
	}
	
	function viewPageAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$page = DevblocksPlatform::importGPC(DevblocksPlatform::importGPC($_REQUEST['page']));
		
		$view = Ps_AbstractViewLoader::getView($id);
		$view->doPage($page);
		Ps_AbstractViewLoader::setView($id, $view);
		
		$view->render();
	}
	
	function viewGetCriteriaAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$field = DevblocksPlatform::importGPC($_REQUEST['field']);
		
		$view = Ps_AbstractViewLoader::getView($id);
		$view->renderCriteria($field);
	}
	
	// Post
	function viewAddCriteriaAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$response_uri = DevblocksPlatform::importGPC($_REQUEST['response_uri']);
		@$field = DevblocksPlatform::importGPC($_REQUEST['field']);
		@$oper = DevblocksPlatform::importGPC($_REQUEST['oper']);
		@$value = DevblocksPlatform::importGPC($_REQUEST['value']);
		
		$view = Ps_AbstractViewLoader::getView($id);
		$view->doSetCriteria($field, $oper, $value);
		Ps_AbstractViewLoader::setView($id, $view);
		
		// [TODO] Need to put them back on org or person (depending on which was active)
		if(!empty($response_uri))
			DevblocksPlatform::redirect(new DevblocksHttpResponse(explode('/', $response_uri)));
	}
	
	function viewRemoveCriteriaAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$response_uri = DevblocksPlatform::importGPC($_REQUEST['response_uri']);
		@$field = DevblocksPlatform::importGPC($_REQUEST['field']);
		
		$view = Ps_AbstractViewLoader::getView($id);
		$view->doRemoveCriteria($field);
		Ps_AbstractViewLoader::setView($id, $view);
		
		if(!empty($response_uri))
			DevblocksPlatform::redirect(new DevblocksHttpResponse(explode('/', $response_uri)));
	}
	
	function viewResetCriteriaAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$response_uri = DevblocksPlatform::importGPC($_REQUEST['response_uri']);
		
		$view = Ps_AbstractViewLoader::getView($id);
		$view->doResetCriteria();
		Ps_AbstractViewLoader::setView($id, $view);

		if(!empty($response_uri))
			DevblocksPlatform::redirect(new DevblocksHttpResponse(explode('/', $response_uri)));
	}
	
	// Ajax
	function viewAddFilterAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		
		@$field = DevblocksPlatform::importGPC($_REQUEST['field']);
		@$oper = DevblocksPlatform::importGPC($_REQUEST['oper']);
		@$value = DevblocksPlatform::importGPC($_REQUEST['value']);
		@$field_deletes = DevblocksPlatform::importGPC($_REQUEST['field_deletes'],'array',array());
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$view = Ps_AbstractViewLoader::getView($id);

		// [TODO] Nuke criteria
		if(is_array($field_deletes) && !empty($field_deletes)) {
			foreach($field_deletes as $field_delete) {
				$view->doRemoveCriteria($field_delete);
			}
		}
		
		if(!empty($field)) {
			$view->doSetCriteria($field, $oper, $value);
		}
		
		$tpl->assign('optColumns', $view->getColumns());
		$tpl->assign('view_fields', $view->getFields());
		$tpl->assign('view_searchable_fields', $view->getSearchFields());
		
		Ps_AbstractViewLoader::setView($id, $view);
		$tpl->assign('view', $view);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'internal/views/customize_view_criteria.tpl');
	}
	
	// Ajax
	function viewCustomizeAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH . '');
		$tpl->assign('id', $id);

		$view = Ps_AbstractViewLoader::getView($id);
		$tpl->assign('view', $view);

		$tpl->assign('optColumns', $view->getColumns());
		$tpl->assign('view_fields', $view->getFields());
		$tpl->assign('view_searchable_fields', $view->getSearchFields());
		
		$tpl->display('file:' . $this->_TPL_PATH . 'internal/views/customize_view.tpl');
	}
	
//	function viewShowCopyAction() {
//        @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
//
//		$active_worker = PortSensorApplication::getActiveWorker();
//
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl_path = $this->_TPL_PATH . '';
//		$tpl->assign('path', $tpl_path);
//        
//        $view = Ps_AbstractViewLoader::getView($view_id);
//
//		$workspaces = DAO_WorkerWorkspaceList::getWorkspaces($active_worker->id);
//		$tpl->assign('workspaces', $workspaces);
//        
//        $tpl->assign('view_id', $view_id);
//		$tpl->assign('view', $view);
//
//        $tpl->display($tpl_path.'internal/views/copy.tpl');
//	}
//	
//	function viewDoCopyAction() {
//		$translate = DevblocksPlatform::getTranslationService();
//		$active_worker = PortSensorApplication::getActiveWorker();
//		$visit = PortSensorApplication::getVisit();
//		
//	    @$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
//		$view = Ps_AbstractViewLoader::getView($view_id);
//	    
//		@$list_title = DevblocksPlatform::importGPC($_POST['list_title'],'string', '');
//		@$workspace = DevblocksPlatform::importGPC($_POST['workspace'],'string', '');
//		@$new_workspace = DevblocksPlatform::importGPC($_POST['new_workspace'],'string', '');
//		
//		if(empty($workspace) && empty($new_workspace))
//			$new_workspace = $translate->_('mail.workspaces.new');
//			
//		if(empty($list_title))
//			$list_title = $translate->_('mail.workspaces.new_list');
//		
//		$workspace_name = (!empty($new_workspace) ? $new_workspace : $workspace);
//		
//        // Find the proper workspace source based on the class of the view
//        $source_manifests = DevblocksPlatform::getExtensions(Extension_WorkspaceSource::EXTENSION_POINT, false);
//        $source_manifest = null;
//        if(is_array($source_manifests))
//        foreach($source_manifests as $mft) {
//        	if(is_a($view, $mft->params['view_class'])) {
//				$source_manifest = $mft;       		
//        		break;
//        	}
//        }
//		
//        if(!is_null($source_manifest)) {
//			// View params inside the list for quick render overload
//			$list_view = new Model_WorkerWorkspaceListView();
//			$list_view->title = $list_title;
//			$list_view->num_rows = $view->renderLimit;
//			$list_view->columns = $view->view_columns;
//			$list_view->params = $view->params;
//			$list_view->sort_by = $view->renderSortBy;
//			$list_view->sort_asc = $view->renderSortAsc;
//			
//			// Save the new worklist
//			$fields = array(
//				DAO_WorkerWorkspaceList::WORKER_ID => $active_worker->id,
//				DAO_WorkerWorkspaceList::WORKSPACE => $workspace_name,
//				DAO_WorkerWorkspaceList::SOURCE_EXTENSION => $source_manifest->id,
//				DAO_WorkerWorkspaceList::LIST_VIEW => serialize($list_view),
//				DAO_WorkerWorkspaceList::LIST_POS => 99,
//			);
//			$list_id = DAO_WorkerWorkspaceList::create($fields);
//        }
//		
//		// Select the workspace tab
//		$visit->set(PortSensorVisit::KEY_HOME_SELECTED_TAB, 'w_'.$workspace_name);
//        
//		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('home')));
//	}

	// Ajax
	function viewShowExportAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH . '');
		$tpl->assign('view_id', $view_id);

		$view = Ps_AbstractViewLoader::getView($view_id);
		$tpl->assign('view', $view);
		
		$model_columns = $view->getColumns();
		$tpl->assign('model_columns', $model_columns);
		
		$view_columns = $view->view_columns;
		$tpl->assign('view_columns', $view_columns);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'internal/views/view_export.tpl');
	}
	
	function viewDoExportAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);
		@$columns = DevblocksPlatform::importGPC($_REQUEST['columns'],'array',array());
		@$export_as = DevblocksPlatform::importGPC($_REQUEST['export_as'],'string','csv');

		// Scan through the columns and remove any blanks
		if(is_array($columns))
		foreach($columns as $idx => $col) {
			if(empty($col))
				unset($columns[$idx]);
		}
		
		$view = Ps_AbstractViewLoader::getView($view_id);
		$column_manifests = $view->getColumns();

		// Override display
		$view->view_columns = $columns;
		$view->renderPage = 0;
		$view->renderLimit = -1;

		if('csv' == $export_as) {
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Type: text/plain; charset=".LANG_CHARSET_CODE);
			
			// Column headers
			if(is_array($columns)) {
				$cols = array();
				foreach($columns as $col) {
					$cols[] = sprintf("\"%s\"",
						str_replace('"','\"',mb_convert_case($column_manifests[$col]->db_label,MB_CASE_TITLE))
					);
				}
				echo implode(',', $cols) . "\r\n";
			}
			
			// Get data
			list($results, $null) = $view->getData();
			if(is_array($results))
			foreach($results as $row) {
				if(is_array($row)) {
					$cols = array();
					if(is_array($columns))
					foreach($columns as $col) {
						$cols[] = sprintf("\"%s\"",
							str_replace('"','\"',$row[$col])
						);
					}
					echo implode(',', $cols) . "\r\n";
				}
			}
			
		} elseif('xml' == $export_as) {
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Type: text/plain; charset=".LANG_CHARSET_CODE);
			
			$xml = simplexml_load_string("<results/>"); /* @var $xml SimpleXMLElement */
			
			// Get data
			list($results, $null) = $view->getData();
			if(is_array($results))
			foreach($results as $row) {
				$result =& $xml->addChild("result");
				if(is_array($columns))
				foreach($columns as $col) {
					$field =& $result->addChild("field",htmlspecialchars($row[$col],null,LANG_CHARSET_CODE));
					$field->addAttribute("id", $col);
				}
			}
		
			// Pretty format and output
			$doc = new DOMDocument('1.0');
			$doc->preserveWhiteSpace = false;
			$doc->loadXML($xml->asXML());
			$doc->formatOutput = true;
			echo $doc->saveXML();			
		}
		
		exit;
	}
	
	// Post?
	function viewSaveCustomizeAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$columns = DevblocksPlatform::importGPC($_REQUEST['columns'],'array', array());
		@$num_rows = DevblocksPlatform::importGPC($_REQUEST['num_rows'],'integer',10);
		
		$num_rows = max($num_rows, 1); // make 1 the minimum
		
		$view = Ps_AbstractViewLoader::getView($id);
		$view->doCustomize($columns, $num_rows);

		$active_worker = PortSensorApplication::getActiveWorker();
		
		// Conditional Persist
//		if(substr($id,0,5)=="cust_") { // custom workspace
//			$list_view_id = intval(substr($id,5));
//			
//			// Special custom view fields
//			@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string', $translate->_('views.new_list'));
//			
//			$view->name = $title;
//
//			// Persist Object
//			$list_view = new Model_WorkerWorkspaceListView();
//			$list_view->title = $title;
//			$list_view->columns = $view->view_columns;
//			$list_view->num_rows = $view->renderLimit;
//			$list_view->params = $view->params;
//			$list_view->sort_by = $view->renderSortBy;
//			$list_view->sort_asc = $view->renderSortAsc;
//			
//			DAO_WorkerWorkspaceList::update($list_view_id, array(
//				DAO_WorkerWorkspaceList::LIST_VIEW => serialize($list_view)
//			));
//			
//		} else {
			$prefs = new Ps_AbstractViewModel();
			$prefs->class_name = get_class($view);
			$prefs->view_columns = $view->view_columns;
			$prefs->renderLimit = $view->renderLimit;
			$prefs->renderSortBy = $view->renderSortBy;
			$prefs->renderSortAsc = $view->renderSortAsc;
			
			DAO_WorkerPref::set($active_worker->id, 'view'.$view->id, serialize($prefs));
//		}
		
		Ps_AbstractViewLoader::setView($id, $view);
		
		$view->render();
	}
	
	function startAutoRefreshAction() {
		$url = DevblocksPlatform::importGPC($_REQUEST['url'],'string', '');
		$secs = DevblocksPlatform::importGPC($_REQUEST['secs'],'integer', 300);
		
		$_SESSION['autorefresh'] = array(
			'url' => $url,
			'started' => time(),
			'secs' => $secs,
		);
	}
	
	function stopAutoRefreshAction() {
		unset($_SESSION['autorefresh']);
	}

	// Post
	function doStopTourAction() {
//		$request = DevblocksPlatform::getHttpRequest();

		$worker = PortSensorApplication::getActiveWorker();
		DAO_WorkerPref::set($worker->id, 'assist_mode', 0);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('home')));
		
//		DevblocksPlatform::redirect(new DevblocksHttpResponse($request->path, $request->query));
	}
	
	// Ajax
	function showCalloutAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'string');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);

		$callouts = PortSensorApplication::getTourCallouts();
		
	    $callout = array();
	    if(isset($callouts[$id]))
	        $callout = $callouts[$id];
		
	    $tpl->assign('callout',$callout);
		
		$tpl->cache_lifetime = "0";
	    $tpl->display('file:' . $this->_TPL_PATH . 'internal/tour/callout.tpl');
	}
	
};

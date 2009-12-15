<?php
class PsRestFrontController extends DevblocksControllerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function handleRequest(DevblocksHttpRequest $request) {
		$controllers = array(
			'notifications' => 'Rest_NotificationsController',
		);

		$stack = $request->path;
		array_shift($stack); // webapi
		
		@$controller = array_shift($stack);

		if(isset($controllers[$controller])) {
			$inst = new $controllers[$controller]();
			if($inst instanceof Ps_RestController) {
				$inst->handleRequest(new DevblocksHttpRequest($stack));
			}
		}
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
	}
};

// [TODO] This should be an extension so people can add new functionality to REST w/ plugins
abstract class Ps_RestController implements DevblocksHttpRequestHandler {
	protected $_format = 'xml';
	protected $_payload = '';
	protected $_activeWorker = null; /* @var $_activeWorker Model_Worker */ 
	
	protected function getActiveWorker() {
		return($this->_activeWorker);
	}
	
	protected function setActiveWorker($worker) {
		$this->_activeWorker = $worker;
	}
		
	function handleRequest(DevblocksHttpRequest $request) {
		$stack = $request->path;
		$db = DevblocksPlatform::getDatabaseService();
		
		// **** BEGIN AUTH
		@$verb = $_SERVER['REQUEST_METHOD'];
		@$header_date = $_SERVER['HTTP_DATE'];
		@$header_signature = $_SERVER['HTTP_PORTSENSOR_AUTH'];
		@$this->_payload = $this->_getRawPost();
		@list($auth_worker_email,$auth_signature) = explode(":", $header_signature, 2);
		$url_parts = parse_url(DevblocksPlatform::getWebPath());
		$url_path = $url_parts['path'];
		$url_query = $this->_sortQueryString($_SERVER['QUERY_STRING']);
		$string_to_sign_prefix = "$verb\n$header_date\n$url_path\n$url_query\n$this->_payload";
		
		if(!$this->_validateRfcDate($header_date)) {
			$this->_error("Access denied! (Invalid timestamp)");
		}
		
//		if(strpos($auth_access_key,'@')) { // WORKER-LEVEL AUTH

		$results = DAO_Worker::getWhere(sprintf("%s = %s", DAO_Worker::EMAIL, $db->qstr($auth_worker_email)));
		
		if(empty($results)) {
			$this->_error("Access denied! (Invalid authentication)");
		} else {
			$worker = array_shift($results);
			$this->setActiveWorker($worker);
		}
		
		if(null == $this->getActiveWorker()) {
			$this->_error("Access denied! (Invalid worker)");
		}

		if(!$worker->hasPriv('plugin.portsensor.webapi')) {
			$this->_error("Access denied! (No permission)");
		}
		
		$pass = $this->getActiveWorker()->pass;
		$string_to_sign = "$string_to_sign_prefix\n$pass\n";
		$compare_hash = base64_encode(sha1($string_to_sign,true));

		if(0 != strcmp($auth_signature,$compare_hash)) {
			$this->_error("Access denied! (Invalid password)");
		}
		// **** END APP AUTH
		
		// Figure out our format by looking at the last path argument
		@list($command,$format) = explode('.', array_pop($stack));
		array_push($stack, $command);
		$this->_format = $format;
		
		// Call the verb as an action
		$method = strtolower($verb) .'Action';
		if(method_exists($this,$method)) {
			call_user_func(array(&$this,$method),$stack);
		} else {
			$this->_error("Invalid action.");
		}
	}
	
	private function _sortQueryString($query) {
		// Strip the leading ?
		if(substr($query,0,1)=='?') $query = substr($query,1);
		$args = array();
		$parts = explode('&', $query);
		foreach($parts as $part) {
			$pair = explode('=', $part, 2);
			if(is_array($pair) && 2==count($pair))
				$args[$pair[0]] = $part;
		}
		ksort($args);
		return implode("&", $args);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $rfcDate
	 * @return boolean
	 */
	private function _validateRfcDate($rfcDate) {
		$diff_allowed = 600; // 10 min
		$mktime_rfcdate = strtotime($rfcDate);
		$mktime_rfcnow = strtotime(date('r'));
		$diff = $mktime_rfcnow - $mktime_rfcdate;
		return ($diff > (-1*$diff_allowed) && $diff < $diff_allowed) ? true : false;
	}
	
	protected function _render($xml) {
		if('json' == $this->_format) {
			header("Content-type: text/javascript; charset=utf-8");
			echo Zend_Json::fromXml($xml, true);
		} else {
			header("Content-type: text/xml; charset=utf-8");
			echo $xml;
		}
		exit;
	}
	
	protected function _error($message) {
		$out_xml = new SimpleXMLElement('<error></error>');
		$out_xml->addChild('message', htmlspecialchars($message));
		$this->_render($out_xml->asXML());
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
	}
	
	protected function getPayload() {
		return $this->_payload;
	}
	
	private function _getRawPost() {
		$contents = "";
		
		$putdata = fopen( "php://input" , "rb" ); 
		while(!feof( $putdata )) 
			$contents .= fread($putdata, 4096); 
		fclose($putdata);

		return $contents;
	}
	
	protected function _renderResults($results, $fields, $element='element', $container='elements', $attribs=array()) {
		$xml = new SimpleXMLElement("<$container/>");

		if(is_array($attribs))
		foreach($attribs as $k=>$v)
			$xml->addAttribute($k, htmlspecialchars($v));

		foreach($results as $result) {
			$e = $xml->addChild($element);
			foreach($fields as $idx => $fld) {
				if((isset($result[$idx])) && ($idx_name = $this->translate($idx, true)) != null)
					$e->addChild($idx_name, htmlspecialchars($result[$idx]));
			}
		}

		$this->_render($xml->asXML());
	}

	protected function _renderOneResult($results, $fields, $element='element') {
		$xml = new SimpleXMLElement("<$element/>");
		$result = array_shift($results);

		foreach($fields as $idx => $fld) {
			if((isset($result[$idx])) && ($idx_name = $this->translate($idx, true)) != null)
				$xml->addChild($idx_name, htmlspecialchars($result[$idx]));
		}

		$this->_render($xml->asXML());
	}
};

class Rest_NotificationsController extends Ps_RestController {
	protected function translate($idx, $dir) {
		$translations = array(
			'we_id' => 'id',
			'we_created_date' => 'created_date',
			'we_worker_id' => 'worker_id',
			'we_title' => 'title',
			'we_content' => 'content',
			'we_is_read' => 'is_read',
			'we_url' => 'url',
		);
		
		if ($dir === true && array_key_exists($idx, $translations))
			return $translations[$idx];
		if ($dir === false)
			return ($key = array_search($idx, $translations)) === false ? null : $key;
		return $idx;
	}
	
	protected function isValid($idx_name, $value) {
		switch($idx_name) {
			case 'created_date':
			case 'worker_id':
				return is_numeric($value) ? true : false;
			case 'is_read':
				return ('1' == $value || '0' == $value) ? true : false;
			case 'url':
			case 'title':
			case 'content':
				return !empty($value) ? true : false;
			default:
				return false;
		}
	}
		
	protected function getAction($path) {
//		if(Model_WebapiKey::ACL_NONE == intval(@$keychain->rights['acl_addresses']))
//			$this->_error("Action not permitted.");
		
		// [TODO] ACL
		
		// Single GET
		if(1==count($path) && is_numeric($path[0]))
			$this->_getIdAction($path);
		
		// Actions
		switch(array_shift($path)) {
			case 'list':
				$this->_getListAction($path);
				break;
		}
	}

	protected function putAction($path) {
//		if(Model_WebapiKey::ACL_FULL!=intval(@$keychain->rights['acl_addresses']))
//			$this->_error("Action not permitted.");

		// [TODO] ACL
		
		// Single PUT
		if(1==count($path) && is_numeric($path[0]))
			$this->_putIdAction($path);
	}
	
	protected function postAction($path) {
		// Actions
		switch(array_shift($path)) {
			case 'create':
//				if(Model_WebapiKey::ACL_FULL!=intval(@$keychain->rights['acl_addresses']))
//					$this->_error("Action not permitted.");
				$this->_postCreateAction($path);
				break;
			case 'search':
//				if(Model_WebapiKey::ACL_NONE==intval(@$keychain->rights['acl_addresses']))
//					$this->_error("Action not permitted.");
				$this->_postSearchAction($path);
				break;
		}
	}
	
	protected function deleteAction($path) {
//		if(Model_WebapiKey::ACL_FULL!=intval(@$keychain->rights['acl_addresses']))
//			$this->_error("Action not permitted.");

		if(1==count($path) && is_numeric($path[0]))
			$this->_deleteIdAction($path);
	}
	
	//****
	
	private function _postCreateAction($path) {
		$xmlstr = $this->getPayload();
		$xml_in = simplexml_load_string($xmlstr);
		
		$fields = array();
		
		$flds = DAO_WorkerEvent::getFields();
		unset($flds[DAO_WorkerEvent::ID]);
		
		foreach($flds as $idx => $f) {
			$idx_name = $this->translate($idx, true);
			if ($idx_name == null) continue;
			@$value = DevblocksPlatform::importGPC($xml_in->$idx_name,'string');
			if($this->isValid($idx_name,$value))
				$fields[$idx] = $value;
		}
		
		if(
			empty($fields[DAO_WorkerEvent::TITLE]) ||
			empty($fields[DAO_WorkerEvent::WORKER_ID]) ||
			empty($fields[DAO_WorkerEvent::CREATED_DATE]) ||
			empty($fields[DAO_WorkerEvent::CONTENT]) ||
			empty($fields[DAO_WorkerEvent::URL])
		)
			$this->_error("All required fields were not provided.");
		
		$id = DAO_WorkerEvent::create($fields);
		
		// Render
		$this->_getIdAction(array($id));
	}
	
	private function _postSearchAction($path) {
		@$p_page = DevblocksPlatform::importGPC($_REQUEST['p'],'integer',0);		
		
		$xml_in = simplexml_load_string($this->getPayload());
		$search_params = SearchFields_WorkerEvent::getFields();
		$params = array();
		
		// Check for params in request
		foreach($search_params as $sp_element => $fld) {
			$sp_element_name = $this->translate($sp_element, true);
			if ($sp_element_name == null) continue;
			@$field_ptr = $xml_in->params->$sp_element_name;
			if(!empty($field_ptr)) {
				@$value = (string) $field_ptr['value'];
				@$oper = (string) $field_ptr['oper'];
				if(empty($oper)) $oper = 'eq';
				$params[$sp_element] =	new DevblocksSearchCriteria($sp_element,$oper,$value);
			}
		}

		list($results, $total) = DAO_WorkerEvent::search(
//			array(),
			$params,
			50,
			$p_page,
			SearchFields_WorkerEvent::CREATED_DATE,
			true,
			true
		);
		
		$attribs = array(
			'page_results' => count($results),
			'total_results' => intval($total)
		);
		
		$this->_renderResults($results, $search_params, 'notification', 'notifications', $attribs);
	}
	
	private function _getIdAction($path) {
		$in_id = array_shift($path);

		if(empty($in_id))
			$this->_error("ID was not provided.");

		list($results, $null) = DAO_WorkerEvent::search(
//			array(),
			array(
				SearchFields_WorkerEvent::ID => new DevblocksSearchCriteria(SearchFields_WorkerEvent::ID,'=',$in_id)
			),
			1,
			0,
			null,
			null,
			false
		);
		
		if(empty($results))
			$this->_error("ID not valid.");
		
		$this->_renderOneResult($results, SearchFields_WorkerEvent::getFields(), 'notification');
	}
	
	private function _getListAction($path) {
		$xml = new SimpleXMLElement("<notifications></notifications>"); 
		@$p_page = DevblocksPlatform::importGPC($_REQUEST['p'],'integer',0);		
		
		list($events,$null) = DAO_WorkerEvent::search(
//			array(),
			array(),
			50,
			$p_page,
			SearchFields_WorkerEvent::CREATED_DATE,
			true,
			false
		);

		$this->_renderResults($events, SearchFields_WorkerEvent::getFields(), 'notification', 'notifications');
	}
	
	private function _putIdAction($path) {
		$xmlstr = $this->getPayload();
		$xml_in = new SimpleXMLElement($xmlstr);
			
		$in_id = array_shift($path);
		
		if(empty($in_id))
			$this->_error("ID was not provided.");
			
		if(null == ($event = DAO_WorkerEvent::get($in_id)))
			$this->_error("ID not valid.");

		$fields = array();
			
		$flds = DAO_WorkerEvent::getFields();
		unset($flds[DAO_WorkerEvent::ID]);
		
		foreach($flds as $idx => $f) {
			$idx_name = $this->translate($idx, true);
			if ($idx_name == null) continue;
			@$value = DevblocksPlatform::importGPC($xml_in->$idx_name,'string');
			if($this->isValid($idx_name,$value))
				$fields[$idx] = $value;
		}
		
		if(!empty($fields))
			DAO_WorkerEvent::update($event->id,$fields);
		
		$this->_getIdAction(array($event->id));
	}
	
	private function _deleteIdAction($path) {
		$in_id = array_shift($path);

		if(empty($in_id))
			$this->_error("ID was not provided.");

		if(null == ($event = DAO_WorkerEvent::get($in_id)))
			$this->_error("ID is not valid.");
			
		DAO_WorkerEvent::delete($event->id);
		
		$out_xml = new SimpleXMLElement('<success></success>');
		$this->_render($out_xml->asXML());
	}
	
};

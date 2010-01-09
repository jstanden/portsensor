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
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

class PsSmsTranslations extends DevblocksTranslationsExtension {
	function __construct($manifest) {
		parent::__construct($manifest);	
	}
	
	function getTmxFile() {
		return dirname(dirname(__FILE__)) . '/strings.xml';
	}
};

// Alert Actions

class PsAlertActionSms extends Extension_AlertAction {
	const EXTENSION_ID = 'portsensor.alert.action.sms';
	
	function __construct($manifest) {
		parent::__construct($manifest);	
	}

	function run(Model_Alert $alert, $sensors) {
    	@$to = DevblocksPlatform::parseCsvString($alert->actions[self::EXTENSION_ID]['to']);
    	@$template_msg = $alert->actions[self::EXTENSION_ID]['template_msg'];
    	
    	$result = true;
    	$logger = DevblocksPlatform::getConsoleLog();
    	
    	// Assign template variables
    	$tpl = DevblocksPlatform::getTemplateService();
    	$tpl->clear_all_assign();
		$tpl->assign('alert', $alert);
		$tpl->assign('sensors', $sensors);
		$tpl->assign('num_sensors', count($sensors));
		
		// Build template
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$text = $tpl_builder->build($template_msg); 
		
		// Clickatell SMS gateways
		$user = $this->getParam('clickatell_username','');
		$password = $this->getParam('clickatell_password','');
		$api_id = $this->getParam('clickatell_api_id','');

		if(empty($user) || empty($password) || empty($api_id))
			return;
		
		if(is_array($to))
		foreach($to as $phone) {
			$logger->info(sprintf("Sending SMS to %s about %d sensors", $phone, count($sensors)));
			
			$url = sprintf("http://api.clickatell.com/http/sendmsg?user=%s&password=%s&api_id=%s&to=%s&text=%s",
				urlencode($user),
				urlencode($password),
				urlencode($api_id),
				urlencode($phone),
				urlencode($text)
			);
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			$out = curl_exec($ch);
			curl_close($ch);
			
			$result = (0==strcasecmp("ID:",substr($out,0,3)));
		}
		
		return $result;
	}
	
	function renderConfig(Model_Alert $alert=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		
		@$params = $alert->actions[self::EXTENSION_ID];
		$tpl->assign('params', $params);

		$tpl->assign('models', array(
			'alert' => get_class_vars("Model_Alert"),
			'sensors[sensor]' => get_class_vars("Model_Sensor"),
		));
		
		$tpl->display($tpl_path . 'alerts/action_sms.tpl');
	}
	
	function saveConfig() { 
    	@$to = DevblocksPlatform::importGPC($_REQUEST['alert_action_sms_to'],'string',null);
    	@$body = DevblocksPlatform::importGPC($_REQUEST['alert_action_sms_tpl'],'string',null);
		
        return array(
			'to' => $to,
        	'template_msg' => $body,
		);
	}
};

class PsSmsSetupTab extends Extension_SetupTab {
	const ID = 'sms.setup.tab';
	
	function showTab() {
		$settings = DevblocksPlatform::getPluginSettingsService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$core_tplpath = dirname(dirname(dirname(__FILE__))) . '/portsensor.core/templates/';
		$tpl->assign('core_tplpath', $core_tplpath);
		$tpl->cache_lifetime = "0";

		$tpl->assign('params', $this->getParams());
		
		$tpl->display('file:' . $tpl_path . 'setup/index.tpl');
	}
	
	function saveTab() {
		@$clickatell_username = DevblocksPlatform::importGPC($_REQUEST['clickatell_username'],'string','');
		@$clickatell_password = DevblocksPlatform::importGPC($_REQUEST['clickatell_password'],'string','');
		@$clickatell_api_id = DevblocksPlatform::importGPC($_REQUEST['clickatell_api_id'],'string','');

		if(!empty($clickatell_username)) {
			$this->setParam('clickatell_username',$clickatell_username);
			$this->setParam('clickatell_password',$clickatell_password);
			$this->setParam('clickatell_api_id',$clickatell_api_id);
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('setup','sms')));
		exit;
	}
	
};
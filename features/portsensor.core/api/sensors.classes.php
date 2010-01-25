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

class PsExternalSensor extends Extension_Sensor {
	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->DevblocksExtension($manifest);
		$this->_TPL_PATH = dirname(dirname(__FILE__)) . '/templates/';
	}
	
	/**
	 * 
	 * @return array 
	 */
	function run(Model_Sensor $sensor, &$fields) {
//		$fields = array(
//			DAO_Sensor::STATUS => ($success?0:2),
//			DAO_Sensor::METRIC => ($success?1:0),
//			DAO_Sensor::OUTPUT => $output,
//		);
//		
//		return $success;
	}
	
	function renderConfig(Model_Sensor $sensor) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('sensor', $sensor);

		$tpl->display('file:' . $this->_TPL_PATH . 'sensors/config/external.tpl');
	}

	function saveConfig(Model_Sensor $sensor) {
		@$mia_secs = DevblocksPlatform::importGPC($_POST['mia_secs'],'integer',0);
		
		$fields = array(
			DAO_Sensor::PARAMS_JSON => json_encode(array(
				'mia_secs' => $mia_secs,
			)),
		);
		
		DAO_Sensor::update($sensor->id, $fields);
	}
};

class PsHttpSensor extends Extension_Sensor {
	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->DevblocksExtension($manifest);
		$this->_TPL_PATH = dirname(dirname(__FILE__)) . '/templates/';
	}
	
	/**
	 * 
	 * @return array 
	 */
	function run(Model_Sensor $sensor, &$fields) {
		$ch = curl_init();
		
		@$url = $sensor->params->url;
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_exec($ch);
		
		$info = curl_getinfo($ch);
		$status = $info['http_code'];

		
		if(200 == $status) {
			$success = true;
			$output = $status;
		} else {
			$success = false;
			$output = curl_error($ch);
		}
		
		curl_close($ch);
		
		$fields = array(
			DAO_Sensor::STATUS => ($success?0:2),
			DAO_Sensor::METRIC => ($success?1:0),
			DAO_Sensor::OUTPUT => $output,
		);
		
		return $success;
	}
	
	function renderConfig(Model_Sensor $sensor) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('sensor', $sensor);

		$tpl->display('file:' . $this->_TPL_PATH . 'sensors/config/http.tpl');
	}

	function saveConfig(Model_Sensor $sensor) {
		@$url = DevblocksPlatform::importGPC($_POST['url'],'string','');
		
		$fields = array(
			DAO_Sensor::PARAMS_JSON => json_encode(array(
				'url' => $url,
			)),
		);
		
		DAO_Sensor::update($sensor->id, $fields);
	}
};

class PsPortSensor extends Extension_Sensor {
	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->DevblocksExtension($manifest);
		$this->_TPL_PATH = dirname(dirname(__FILE__)) . '/templates/';
	}
	
	/**
	 * 
	 * @return array 
	 */
	function run(Model_Sensor $sensor, &$fields) {
		// [TODO] Make timeout configurable

		$errno = null;
		$errstr = null;
		
		@$host = $sensor->params->host;
		@$port = intval($sensor->params->port);
		
		if(false !== (@$conn = fsockopen($host, $port, $errno, $errstr, 10))) {
			$success = true;
			$output = fgets($conn);
			fclose($conn);
		} else {
			$success = false;
			$output = $errstr;
		}
		
		$fields = array(
			DAO_Sensor::STATUS => ($success?0:2),
			DAO_Sensor::METRIC => ($success?1:0),
			DAO_Sensor::OUTPUT => $output,
		);
		
		return $success;
	}
	
	function renderConfig(Model_Sensor $sensor) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('sensor', $sensor);

		$tpl->display('file:' . $this->_TPL_PATH . 'sensors/config/port.tpl');
	}

	function saveConfig(Model_Sensor $sensor) {
		@$host = DevblocksPlatform::importGPC($_POST['host'],'string','');
		@$port = DevblocksPlatform::importGPC($_POST['port'],'integer',0);
		
		$fields = array(
			DAO_Sensor::PARAMS_JSON => json_encode(array(
				'host' => $host,
				'port' => $port,
			)),
		);
		
		DAO_Sensor::update($sensor->id, $fields);
	}
};

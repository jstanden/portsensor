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

class PsHttpSensor extends Extension_Sensor {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	/**
	 * 
	 * @return array 
	 */
	function run(Model_Sensor $sensor, &$fields) {
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, "http://www.cerb4.com/");
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_exec($ch);
		
		$info = curl_getinfo($ch);
		$status = $info['http_code'];

		curl_close($ch);
		
		$success = (200==$status);
		
		$fields = array(
			DAO_Sensor::STATUS => ($success?0:2),
			DAO_Sensor::METRIC => ($success?1:0),
			DAO_Sensor::METRIC_TYPE => 'U',
			DAO_Sensor::OUTPUT => ($success?'UP':'DOWN'),
		);
		
		return $success;
	}
};

class PsPortSensor extends Extension_Sensor {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	/**
	 * 
	 * @return array 
	 */
	function run(Model_Sensor $sensor, &$fields) {
		// [TODO] Make timeout configurable

		$errno = null;
		$errstr = null;
		
		if(false !== (@$conn = fsockopen('xev3.webgroupmedia.com', 25, $errno, $errstr, 10))) {
			$success = true;
			fclose($conn);
		} else {
			$success = false;
		}
		
		$fields = array(
			DAO_Sensor::STATUS => ($success?0:2),
			DAO_Sensor::METRIC => ($success?1:0),
			DAO_Sensor::METRIC_TYPE => 'U',
			DAO_Sensor::OUTPUT => ($success?'UP':('DOWN:'.$errstr)),
		);
		
		return $success;
	}
};

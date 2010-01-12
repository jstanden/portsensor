<?php
class PsCoreTour extends DevblocksHttpResponseListenerExtension implements IDevblocksTourListener {
	function __construct($manifest) {
		parent::__construct($manifest);
	}

	/**
	 * @return DevblocksTourCallout[]
	 */
	function registerCallouts() {
		return array(
//			'tourHeaderMenu' => new DevblocksTourCallout('tourHeaderMenu','Helpdesk Menu','This is where you can change between major helpdesk sections.'),
			'' => new DevblocksTourCallout('',''),
		);
	}
	
	function run(DevblocksHttpResponse $response, Smarty $tpl) {
		$path = $response->path;

		$callouts = PortSensorApplication::getTourCallouts();

		switch(array_shift($path)) {
			case 'welcome':
				$tour = array(
	                'title' => 'Welcome!',
	                'body' => "This assistant will help you become familiar with the application by following along and providing information about the current page.  You may follow the 'Points of Interest' links highlighted below to read tips about nearby functionality.",
	                'callouts' => array(
						$callouts['tourHeaderMenu'],
					)
				);
				break;

			case "preferences":
				$tour = array(
					'title' => 'Preferences',
					'body' => 'This screen allows you to change the personal preferences on your account.',
				);
				break;

			case "setup":
				$tour = array(
					'title' => 'Setup',
					'body' => '...',
				);
				break;
				
			case NULL:
			case 'home':
				$tour = array(
	                'title' => 'Home',
	                'body' => '...',
	                'callouts' => array(
					)
				);
				break;
				
		}

		if(!empty($tour))
			$tpl->assign('tour', $tour);
	}
};

class PsCoreEventListener extends DevblocksEventListenerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}

	/**
	 * @param Model_DevblocksEvent $event
	 */
	function handleEvent(Model_DevblocksEvent $event) {
		// PortSensor Workflow
		switch($event->id) {
			case 'cron.heartbeat':
				$this->_handleCronHeartbeat($event);
				break;
				
			case 'cron.maint':
				$this->_handleCronMaint($event);
				break;

			case 'cron.sensors.post':
				$this->_handleCronSensorsPost($event);
				break;
		}
	}

	private function _handleCronMaint($event) {
//		DAO_Address::maint();
//		DAO_Group::maint();
//		DAO_Ticket::maint();
//		DAO_Message::maint();
//		DAO_Worker::maint();
	}
	
	private function _handleCronHeartbeat($event) {
		// ... Do something
	}
	
	private function _handleCronSensorsPost($event) {
		$logger = DevblocksPlatform::getConsoleLog();
		$sensors = DAO_Sensor::getAll();
		
		// Check that all external sensors aren't over their M.I.A. time
		if(is_array($sensors))
		foreach($sensors as $sensor) { /* @var $sensor Model_Sensor */
			if('sensor.external' != $sensor->extension_id)
				continue;
				
			$mia_secs = intval($sensor->params->mia_secs);
			$elapsed = time() - $sensor->updated_date;
			
			if($mia_secs && $elapsed > $mia_secs) {
				$fields = array(
					DAO_Sensor::STATUS => 3, // MIA
					DAO_Sensor::FAIL_COUNT => intval($sensor->fail_count) + 1,
					DAO_Sensor::METRIC => '',
					DAO_Sensor::OUTPUT => '',
				);
				DAO_Sensor::update($sensor->id, $fields);
				
				$logger->info($sensor->name . " is M.I.A. for $elapsed seconds.");
			}
		}
	}

};

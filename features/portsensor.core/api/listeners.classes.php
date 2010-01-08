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

};

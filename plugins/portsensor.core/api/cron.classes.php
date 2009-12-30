<?php
class MaintCron extends PortSensorCronExtension {
	function run() {
		$logger = DevblocksPlatform::getConsoleLog();
		$logger->info("[Maint] Starting...");
		
		@ini_set('memory_limit','64M');

		$db = DevblocksPlatform::getDatabaseService();

		// Give plugins a chance to run maintenance (nuke NULL rows, etc.)
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'cron.maint',
                array()
            )
	    );
	  
//		// [JAS] Remove any empty directories inside storage/import/new
//		$importNewDir = APP_STORAGE_PATH . '/import/new' . DIRECTORY_SEPARATOR;
//		$subdirs = glob($importNewDir . '*', GLOB_ONLYDIR);
//		if ($subdirs !== false) {
//			foreach($subdirs as $subdir) {
//				$directory_empty = count(glob($subdir. DIRECTORY_SEPARATOR . '*')) === 0;
//				if($directory_empty && is_writeable($subdir)) {
//					rmdir($subdir);
//				}
//			}
//		}
//		
//		$logger->info('[Maint] Cleaned up import directories.');
		$logger->info("[Maint] Finished!");
	}

	function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);

//		$tpl->assign('purge_waitdays', $this->getParam('purge_waitdays', 7));

		$tpl->display($tpl_path . 'cron/maint/config.tpl');
	}

	function saveConfigurationAction() {
//		@$purge_waitdays = DevblocksPlatform::importGPC($_POST['purge_waitdays'],'integer');
//		$this->setParam('purge_waitdays', $purge_waitdays);
	}
};

/**
 * Plugins can implement an event listener on the heartbeat to do any kind of
 * time-dependent or interval-based events.  For example, doing a workflow
 * action every 5 minutes.
 */
class HeartbeatCron extends PortSensorCronExtension {
	function run() {
		$logger = DevblocksPlatform::getConsoleLog();
		$logger->info("[Heartbeat] Starting...");
		
		// Heartbeat Event
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
	            'cron.heartbeat',
				array(
				)
			)
		);
		
		$logger->info("[Heartbeat] Finished!");
	}

	function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);

		$tpl->display($tpl_path . 'cron/heartbeat/config.tpl');
	}
};

/**
 */
class Cron_SensorRunner extends PortSensorCronExtension {
	function run() {
		$logger = DevblocksPlatform::getConsoleLog();
		$logger->info("[Sensors] Starting...");

		// Only pull enabled sensors with an extension_id
		$sensors = DAO_Sensor::getWhere(
			sprintf("%s=%d AND %s!=''", 
				DAO_Sensor::IS_DISABLED,
				0,
				DAO_Sensor::EXTENSION_ID
			)
		);
		$sensor_types = DevblocksPlatform::getExtensions('portsensor.sensor', true);
		
		if(is_array($sensors))
		foreach($sensors as $sensor) {
			if(!empty($sensor->extension_id) && isset($sensor_types[$sensor->extension_id])) {
				$runner = $sensor_types[$sensor->extension_id]; 
				$output = sprintf("%s (%s)",
					$sensor->name,
					$sensor->extension_id
				);
				if(method_exists($runner,'run')) {
					$fields = array();
					$success = $runner->run($sensor, $fields);
					
					$fields[DAO_Sensor::UPDATED_DATE] = time();
					$fields[DAO_Sensor::FAIL_COUNT] = ($success ? 0 : (intval($sensor->fail_count)+1));
					
					DAO_Sensor::update($sensor->id, $fields);
				}
				$logger->info("[Sensors] Running $output... $result");
			}	
		}
		
		// Sensor Runner Event
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
	            'cron.sensors',
				array(
				)
			)
		);
		
		$logger->info("[Sensors] Finished!");
	}

	function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);

		$tpl->display($tpl_path . 'cron/sensors/config.tpl');
	}
};

class Cron_Alerts extends PortSensorCronExtension {
	function run() {
		$logger = DevblocksPlatform::getConsoleLog();
		$logger->info("[Alerts] Starting...");
		
//		$notify_sensors = DAO_Sensor::getWhere(sprintf("%s > %d", DAO_Sensor::STATUS, 0));
//		print_r($notify_sensors);
		
		$logger->info("[Alerts] Finished!");
	}

	function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);

		$tpl->display($tpl_path . 'cron/alerts/config.tpl');
	}
};

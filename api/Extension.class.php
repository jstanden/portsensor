<?php
abstract class PortSensorPageExtension extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	function isVisible() { return true; }
	function render() { }
	
	/**
	 * @return Model_Activity
	 */
	public function getActivity() {
        return new Model_Activity('activity.default');
	}
};

abstract class Extension_Sensor extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	/**
	 * 
	 * @return array 
	 */
	function run(Model_Sensor $sensor) { }

	/**
	 *
	 */
	function renderConfig(Model_Sensor $sensor) { }

	/**
	 *
	 */
	function saveConfig(Model_Sensor $sensor) { }
};

abstract class PortSensorCronExtension extends DevblocksExtension {
    const PARAM_ENABLED = 'enabled';
    const PARAM_LOCKED = 'locked';
    const PARAM_DURATION = 'duration';
    const PARAM_TERM = 'term';
    const PARAM_LASTRUN = 'lastrun';
    
	function __construct($manifest) {
		$this->DevblocksExtension($manifest, 1);
	}

	/**
	 * runs scheduled task
	 *
	 */
	function run() {
	    // Overloaded by child
	}
	
	function _run() {
	    $this->run();
	    
		$duration = $this->getParam(self::PARAM_DURATION, 5);
		$term = $this->getParam(self::PARAM_TERM, 'm');
	    $lastrun = $this->getParam(self::PARAM_LASTRUN, time());

	    $secs = self::getIntervalAsSeconds($duration, $term);
	    $ran_at = time();
	    
	    if(!empty($secs)) {
		    $gap = time() - $lastrun; // how long since we last ran
		    $extra = $gap % $secs; // we waited too long to run by this many secs
		    $ran_at = time() - $extra; // go back in time and lie
	    }
	    
	    $this->setParam(self::PARAM_LASTRUN,$ran_at);
	    $this->setParam(self::PARAM_LOCKED,0);
	}
	
	/**
	 * @param boolean $is_ignoring_wait Ignore the wait time when deciding to run
	 * @return boolean
	 */
	public function isReadyToRun($is_ignoring_wait=false) {
		$locked = $this->getParam(self::PARAM_LOCKED, 0);
		$enabled = $this->getParam(self::PARAM_ENABLED, false);
		$duration = $this->getParam(self::PARAM_DURATION, 5);
		$term = $this->getParam(self::PARAM_TERM, 'm');
		$lastrun = $this->getParam(self::PARAM_LASTRUN, 0);
		
		// If we've been locked too long then unlock
	    if($locked && $locked < (time() - 10 * 60)) {
	        $locked = 0;
	    }

	    // Make sure enough time has elapsed.
	    $checkpoint = ($is_ignoring_wait)
	    	? (0) // if we're ignoring wait times, be ready now
	    	: ($lastrun + self::getIntervalAsSeconds($duration, $term)) // otherwise test
	    	;

	    // Ready?
	    return (!$locked && $enabled && time() >= $checkpoint) ? true : false;
	}
	
	static public function getIntervalAsSeconds($duration, $term) {
	    $seconds = 0;
	    
	    if($term=='d') {
	        $seconds = $duration * 24 * 60 * 60; // x hours * mins * secs
	    } elseif($term=='h') {
	        $seconds = $duration * 60 * 60; // x * mins * secs
	    } else {
	        $seconds = $duration * 60; // x * secs
	    }
	    
	    return $seconds;
	}
	
	public function configure($instance) {}
	
	public function saveConfigurationAction() {}
};

abstract class Extension_AppPreBodyRenderer extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	function render() { }
};

abstract class Extension_AppPostBodyRenderer extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	function render() { }
};

abstract class Extension_CustomFieldSource extends DevblocksExtension {
	const EXTENSION_POINT = 'portsensor.fields.source';
	
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
};

abstract class Extension_SetupTab extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	function showTab() {}
	function saveTab() {}
};

abstract class Extension_PreferenceTab extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	function showTab() {}
	function saveTab() {}
};

abstract class Extension_HomeTab extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	function showTab() {}
	function saveTab() {}
};

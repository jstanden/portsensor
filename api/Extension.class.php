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

//abstract class Extension_AppPreBodyRenderer extends DevblocksExtension {
//	function __construct($manifest) {
//		$this->DevblocksExtension($manifest,1);
//	}
//	
//	function render() { }
//};
//
//abstract class Extension_AppPostBodyRenderer extends DevblocksExtension {
//	function __construct($manifest) {
//		$this->DevblocksExtension($manifest,1);
//	}
//	
//	function render() { }
//};

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

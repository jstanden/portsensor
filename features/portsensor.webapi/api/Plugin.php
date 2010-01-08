<?php
class PsRestPlugin {
	const PLUGIN_ID = 'portsensor.controller.rest';
};

if (class_exists('DevblocksTranslationsExtension',true)):
	class PsWebApiTranslations extends DevblocksTranslationsExtension {
		function __construct($manifest) {
			parent::__construct($manifest);	
		}
		
		function getTmxFile() {
			return dirname(dirname(__FILE__)) . '/strings.xml';
		}
	};
endif;

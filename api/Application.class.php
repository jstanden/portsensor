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
define("APP_BUILD", 2010011101);

require_once(APP_PATH . "/api/DAO.class.php");
require_once(APP_PATH . "/api/Model.class.php");
require_once(APP_PATH . "/api/Extension.class.php");

// App Scope ClassLoading
$path = APP_PATH . '/api/app/';

DevblocksPlatform::registerClasses($path . 'Update.php', array(
	'PsUpdateController',
));

/**
 * Application-level Facade
 */
class PortSensorApplication extends DevblocksApplication {
	
	/**
	 * @return CerberusVisit
	 */
	static function getVisit() {
		$session = DevblocksPlatform::getSessionService();
		return $session->getVisit();
	}
	
	/**
	 * @return Model_Worker
	 */
	static function getActiveWorker() {
		$visit = self::getVisit();
		return (null != $visit) 
			? $visit->getWorker()
			: null
			;
	}
	
	static function processRequest(DevblocksHttpRequest $request, $is_ajax=false) {
		/**
		 * Override the 'update' URI since we can't count on the database 
		 * being populated from XML beforehand when /update loads it.
		 */
		if(!$is_ajax && isset($request->path[0]) && 0 == strcasecmp($request->path[0],'update')) {
			if(null != ($update_controller = new PsUpdateController(null)))
				$update_controller->handleRequest($request);
			
		} else {
			// Hand it off to the platform
			DevblocksPlatform::processRequest($request, $is_ajax);
		}
	}
	
	static function checkRequirements() {
		$errors = array();
		
		// Privileges
		
		// Make sure the temporary directories of Devblocks are writeable.
		if(!is_writeable(APP_TEMP_PATH)) {
			$errors[] = APP_TEMP_PATH ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		if(!file_exists(APP_TEMP_PATH . "/templates_c")) {
			@mkdir(APP_TEMP_PATH . "/templates_c");
		}
		
		if(!is_writeable(APP_TEMP_PATH . "/templates_c/")) {
			$errors[] = APP_TEMP_PATH . "/templates_c/" . " is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}

		if(!file_exists(APP_TEMP_PATH . "/cache")) {
			@mkdir(APP_TEMP_PATH . "/cache");
		}
		
		if(!is_writeable(APP_TEMP_PATH . "/cache/")) {
			$errors[] = APP_TEMP_PATH . "/cache/" . " is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
		if(!is_writeable(APP_STORAGE_PATH)) {
			$errors[] = APP_STORAGE_PATH ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
		}
		
//		if(!is_writeable(APP_STORAGE_PATH . "/import/fail")) {
//			$errors[] = APP_STORAGE_PATH . "/import/fail/" ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
//		}
//		
//		if(!is_writeable(APP_STORAGE_PATH . "/import/new")) {
//			$errors[] = APP_STORAGE_PATH . "/import/new/" ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
//		}
//		
//		if(!is_writeable(APP_STORAGE_PATH . "/attachments/")) {
//			$errors[] = APP_STORAGE_PATH . "/attachments/" ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
//		}
//		
//		if(!is_writeable(APP_STORAGE_PATH . "/mail/new/")) {
//			$errors[] = APP_STORAGE_PATH . "/mail/new/" ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
//		}
		
//		if(!is_writeable(APP_STORAGE_PATH . "/mail/fail/")) {
//			$errors[] = APP_STORAGE_PATH . "/mail/fail/" ." is not writeable by the webserver.  Please adjust permissions and reload this page.";
//		}
		
		// Requirements
		
		// PHP Version
		if(version_compare(PHP_VERSION,"5.2") >=0) {
		} else {
			$errors[] = 'PortSensor requires PHP 5.2 or later. Your server PHP version is '.PHP_VERSION;
		}
		
		// File Uploads
		$ini_file_uploads = ini_get("file_uploads");
		if($ini_file_uploads == 1 || strcasecmp($ini_file_uploads,"on")==0) {
		} else {
			$errors[] = 'file_uploads is disabled in your php.ini file. Please enable it.';
		}
		
		// Memory Limit
		$memory_limit = ini_get("memory_limit");
		if ($memory_limit == '') { // empty string means failure or not defined, assume no compiled memory limits
		} else {
			$ini_memory_limit = intval($memory_limit);
			if($ini_memory_limit >= 16) {
			} else {
				$errors[] = 'memory_limit must be 16M or larger (32M recommended) in your php.ini file.  Please increase it.';
			}
		}
		
		// Extension: MySQL
		if(extension_loaded("mysql")) {
		} else {
			$errors[] = "The 'MySQL' PHP extension is required.  Please enable it.";
		}
		
		// Extension: Sessions
		if(extension_loaded("session")) {
		} else {
			$errors[] = "The 'Session' PHP extension is required.  Please enable it.";
		}
		
		// Extension: PCRE
		if(extension_loaded("pcre")) {
		} else {
			$errors[] = "The 'PCRE' PHP extension is required.  Please enable it.";
		}
		
		// Extension: GD
		if(extension_loaded("gd") && function_exists('imagettfbbox')) {
		} else {
			$errors[] = "The 'GD' PHP extension (with FreeType library support) is required.  Please enable them.";
		}
		
		// Extension: IMAP
		if(extension_loaded("imap")) {
		} else {
			$errors[] = "The 'IMAP' PHP extension is required.  Please enable it.";
		}
		
//		// Extension: MailParse
//		if(extension_loaded("mailparse")) {
//		} else {
//			$errors[] = "The 'MailParse' PHP extension is required.  Please enable it.";
//		}
		
		// Extension: mbstring
		if(extension_loaded("mbstring")) {
		} else {
			$errors[] = "The 'MbString' PHP extension is required.  Please	enable it.";
		}
		
		// Extension: XML
		if(extension_loaded("xml")) {
		} else {
			$errors[] = "The 'XML' PHP extension is required.  Please enable it.";
		}
		
		// Extension: cURL
		if(extension_loaded("curl")) {
		} else {
			$errors[] = "The 'cURL' PHP extension is required.  Please enable it.";
		}
		
		// Extension: SimpleXML
		if(extension_loaded("simplexml")) {
		} else {
			$errors[] = "The 'SimpleXML' PHP extension is required.  Please enable it.";
		}
		
		// Extension: DOM
		if(extension_loaded("dom")) {
		} else {
			$errors[] = "The 'DOM' PHP extension is required.  Please enable it.";
		}
		
		// Extension: SPL
		if(extension_loaded("spl")) {
		} else {
			$errors[] = "The 'SPL' PHP extension is required.  Please enable it.";
		}
		
		// Extension: JSON
		if(extension_loaded("json")) {
		} else {
			$errors[] = "The 'JSON' PHP extension is required.  Please enable it.";
		}
		
		return $errors;
	}
	
	static function generatePassword($length=8) {
		$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ123456789';
		$len = strlen($chars)-1;
		$password = '';
		
		for($x=0;$x<$length;$x++) {
			$chars = str_shuffle($chars);
			$password .= substr($chars,mt_rand(0,$len),1);
		}
		
		return $password;		
	}
	
	// [JAS]: [TODO] Cleanup + move (platform, diff ext point, DAO?)
	/**
	 * @return DevblocksTourCallout[]
	 */
	static function getTourCallouts() {
	    static $callouts = null;
	    
	    if(!is_null($callouts))
	        return $callouts;
	    
	    $callouts = array();
	        
	    $listenerManifests = DevblocksPlatform::getExtensions('devblocks.listener.http');
	    foreach($listenerManifests as $listenerManifest) { /* @var $listenerManifest DevblocksExtensionManifest */
	         $inst = $listenerManifest->createInstance(); /* @var $inst IDevblocksTourListener */
	         
	         if($inst instanceof IDevblocksTourListener)
	             $callouts += $inst->registerCallouts();
	    }
	    
	    return $callouts;
	}
	
	static function stripHTML($str) {
		// Strip all CRLF and tabs, spacify </TD>
		$str = str_ireplace(
			array("\r","\n","\t","</TD>"),
			array('','',' ',' '),
			trim($str)
		);
		
		// Turn block tags into a linefeed
		$str = str_ireplace(
			array('<BR>','<P>','</P>','<HR>','</TR>','</H1>','</H2>','</H3>','</H4>','</H5>','</H6>','</DIV>'),
			"\n",
			$str
		);		
		
		// Strip tags
		$search = array(
			'@<script[^>]*?>.*?</script>@si',
		    '@<style[^>]*?>.*?</style>@siU',
		    '@<[\/\!]*?[^<>]*?>@si',
		    '@<![\s\S]*?--[ \t\n\r]*>@',
		);
		$str = preg_replace($search, '', $str);
		
		// Flatten multiple spaces into a single
		$str = preg_replace('# +#', ' ', $str);

		// Translate HTML entities into text
		$str = html_entity_decode($str, ENT_COMPAT, LANG_CHARSET_CODE);

		// Loop through each line, ltrim, and concat if not empty
		$lines = explode("\n", $str);
		if(is_array($lines)) {
			$str = '';
			$blanks = 0;
			foreach($lines as $idx => $line) {
				$lines[$idx] = ltrim($line);
				
				if(empty($lines[$idx])) {
					if(++$blanks >= 2)
						unset($lines[$idx]);
						//continue; // skip more than 2 blank lines in a row
				} else {
					$blanks = 0;
				}
			}
			$str = implode("\n", $lines);
		}
		unset($lines);
		
		// Clean up bytes (needed after HTML entities)
		$str = mb_convert_encoding($str, LANG_CHARSET_CODE, LANG_CHARSET_CODE);
		
		return $str;
	}
	    
};

class PortSensorLicense {
	public $name = '';
	public $users = 3;
	public $key = '';
	
	/**
	 * @return array
	 */
	public static function getInstance() {
		$settings = DevblocksPlatform::getPluginSettingsService();
		$license = $settings->get('portsensor.core',PortSensorSettings::LICENSE,array());
		if(!empty($license)) {
			@$license = unserialize($license);
		}
		if(!is_array($license))
			$license = array();
		return $license;
	}

	public static function validate($key, $email) {
		/*  																																																																																																																														*/foreach(array('L0NvbXBhbnk6ICguKikv'=>'b','L1VzZXJzOiAoLiopLw=='=>'d','L1NlcmlhbDogKC4qKS8='=>'s') as $k=>$v)@preg_match(base64_decode($k),$key,$matches)?@$$v=trim($matches[1]):null;@$r=array('name'=>$b,'email'=>$email,'users'=>intval($d),'serial'=>$s);foreach(array(chr(97)=>0,chr(101)=>3) as $k=>$v)if(@substr(str_replace('-','',$s),0,1).@substr(str_replace('-','',$s),4,1).@substr(str_replace('-','',$s),8,1)==@substr(strtoupper(md5(@substr($b,0,1).@substr($b,-1,1).@strlen($b).$d.@substr($email,0,1).@substr($email,4,1).@strlen($email))),$v,3))@$r[$k]=$s;return $r;/*
		 * we're sure being generous here! [TODO]
		 */
		$lines = explode("\n", $key);
		
		/*
		 * Remember that our cache can return stale data here. Be sure to
		 * clear caches.  The config area does already.
		 */
		return (!empty($key)) 
			? array(
				'name' => (list($k,$v)=explode(":",$lines[1]))?trim($v):null,
				'email' => $email,
				'users' => (list($k,$v)=explode(":",$lines[2]))?trim($v):null,
				'serial' => (list($k,$v)=explode(":",$lines[3]))?trim($v):null,
				'date' => time()
			)
			: null;
	}
};

class PortSensorMail {
	private function __construct() {}
	
	static function getMailerDefaults() {
		$settings = DevblocksPlatform::getPluginSettingsService();

		return array(
			'host' => $settings->get('portsensor.core',PortSensorSettings::SMTP_HOST,'localhost'),
			'port' => $settings->get('portsensor.core',PortSensorSettings::SMTP_PORT,'25'),
			'auth_user' => $settings->get('portsensor.core',PortSensorSettings::SMTP_AUTH_USER,null),
			'auth_pass' => $settings->get('portsensor.core',PortSensorSettings::SMTP_AUTH_PASS,null),
			'enc' => $settings->get('portsensor.core',PortSensorSettings::SMTP_ENCRYPTION_TYPE,'None'),
			'max_sends' => $settings->get('portsensor.core',PortSensorSettings::SMTP_MAX_SENDS,20),
			'timeout' => $settings->get('portsensor.core',PortSensorSettings::SMTP_TIMEOUT,30),
		);
	}
	
	static function quickSend($to, $subject, $body, $from_addy=null, $from_personal=null) {
		try {
			$mail_service = DevblocksPlatform::getMailService();
			$mailer = $mail_service->getMailer(PortSensorMail::getMailerDefaults());
			$mail = $mail_service->createMessage();
	
		    $settings = DevblocksPlatform::getPluginSettingsService();
		    
		    if(empty($from_addy))
				@$from_addy = $settings->get('portsensor.core',PortSensorSettings::DEFAULT_REPLY_FROM, $_SERVER['SERVER_ADMIN']);
		    
		    if(empty($from_personal))
				@$from_personal = $settings->get('portsensor.core',PortSensorSettings::DEFAULT_REPLY_PERSONAL,'');
			
			$mail->setTo(array($to));
			$mail->setFrom(array($from_addy => $from_personal));
			$mail->setSubject($subject);
			$mail->generateId();
			
			$headers = $mail->getHeaders();
			
			$headers->addTextHeader('X-Mailer','PortSensor (Build '.APP_BUILD.')');
			
			$mail->setBody($body);
		
			// [TODO] Report when the message wasn't sent.
			if(!$mailer->send($mail)) {
				return false;
			}
			
		} catch (Exception $e) {
			return false;
		}
		
		return true;
	}
};

class PortSensorSettings {
	const APP_TITLE = 'app_title';
	const APP_LOGO_URL = 'app_logo_url';
	const DEFAULT_REPLY_FROM = 'default_reply_from';
	const DEFAULT_REPLY_PERSONAL = 'default_reply_personal';
	const SMTP_HOST = 'smtp_host';
	const SMTP_AUTH_ENABLED = 'smtp_auth_enabled'; 
	const SMTP_AUTH_USER = 'smtp_auth_user';
	const SMTP_AUTH_PASS = 'smtp_auth_pass'; 
	const SMTP_PORT = 'smtp_port';
	const SMTP_ENCRYPTION_TYPE = 'smtp_enc';
	const SMTP_MAX_SENDS = 'smtp_max_sends';
	const SMTP_TIMEOUT = 'smtp_timeout';
	const AUTHORIZED_IPS = 'authorized_ips';
	const LICENSE = 'license';
	const ACL_ENABLED = 'acl_enabled';
};

// [TODO] This gets called a lot when it happens after the registry cache
class PS_DevblocksExtensionDelegate implements DevblocksExtensionDelegate {
	static function shouldLoadExtension(DevblocksExtensionManifest $extension_manifest) {
		// Always allow core
		if("portsensor.core" == $extension_manifest->plugin_id)
			return true;
		
		// [TODO] This should limit to just things we can run with no session
		// Community Tools, Cron/Update.  They are still limited by their own
		// isVisible() otherwise.
		if(null == ($active_worker = PortSensorApplication::getActiveWorker()))
			return true;
		
		// [TODO] ACL
		//return $active_worker->hasPriv('plugin.'.$extension_manifest->plugin_id);
		
		return true;
	}
};

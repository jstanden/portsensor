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

define('INSTALL_APP_NAME', 'PortSensor');

// -----------

if(version_compare(PHP_VERSION, "5.2", "<"))
	die("PortSensor requires PHP 5.2 or later.");

@set_time_limit(3600);
require('../framework.config.php');
require_once(DEVBLOCKS_PATH . 'Devblocks.class.php');
require_once(APP_PATH . '/api/Application.class.php');
require_once(APP_PATH . '/install/classes.php');

DevblocksPlatform::getCacheService()->clean();

// DevblocksPlatform::init() workaround 
if(!defined('DEVBLOCKS_WEBPATH')) {
	$php_self = $_SERVER["PHP_SELF"];
	$php_self = str_replace('/install','',$php_self);
	$pos = strrpos($php_self,'/');
	$php_self = substr($php_self,0,$pos) . '/';
	@define('DEVBLOCKS_WEBPATH',$php_self);
	@define('DEVBLOCKS_APP_WEBPATH',$php_self);
}

define('STEP_ENVIRONMENT', 1);
define('STEP_LICENSE', 2);
define('STEP_DATABASE', 3);
define('STEP_SAVE_CONFIG_FILE', 4);
define('STEP_INIT_DB', 5);
define('STEP_CONTACT', 6);
define('STEP_OUTGOING_MAIL', 7);
define('STEP_DEFAULTS', 8);
define('STEP_REGISTER', 9);
define('STEP_UPGRADE', 10);
define('STEP_FINISHED', 11);

define('TOTAL_STEPS', 11);

// Import GPC variables to determine our scope/step.
@$step = DevblocksPlatform::importGPC($_REQUEST['step'],'integer');

/*
 * [TODO] We can run some quick tests to bypass steps we've already passed
 * even when returning to the page with a NULL step.
 */
if(empty($step)) $step = STEP_ENVIRONMENT;

// [TODO] Could convert to PortSensorApplication::checkRequirements()

@chmod(APP_TEMP_PATH, 0774);
@mkdir(APP_TEMP_PATH . '/templates_c/');
@chmod(APP_TEMP_PATH . '/templates_c/', 0774);
@mkdir(APP_TEMP_PATH . '/cache/');
@chmod(APP_TEMP_PATH . '/cache/', 0774);

// Make sure the temporary directories of Devblocks are writeable.
if(!is_writeable(APP_TEMP_PATH)) {
	die(APP_TEMP_PATH ." is not writeable by the webserver.  Please adjust permissions and reload this page.");
}

if(!is_writeable(APP_TEMP_PATH . "/templates_c/")) {
	die(APP_TEMP_PATH . "/templates_c/" . " is not writeable by the webserver.  Please adjust permissions and reload this page.");
}

if(!is_writeable(APP_TEMP_PATH . "/cache/")) {
	die(APP_TEMP_PATH . "/cache/" . " is not writeable by the webserver.  Please adjust permissions and reload this page.");
}

@chmod(APP_STORAGE_PATH, 0774);
@chmod(APP_STORAGE_PATH . '/attachments/', 0774);
@chmod(APP_STORAGE_PATH . '/mail/new/', 0774);
@chmod(APP_STORAGE_PATH . '/mail/fail/', 0774);

if(!is_writeable(APP_STORAGE_PATH)) {
	die(APP_STORAGE_PATH . " is not writeable by the webserver.  Please adjust permissions and reload this page.");
}

//if(!is_writeable(APP_STORAGE_PATH . "/import/fail/")) {
//	die(APP_STORAGE_PATH . "/import/fail/ is not writeable by the webserver.  Please adjust permissions and reload this page.");
//}
//
//if(!is_writeable(APP_STORAGE_PATH . "/import/new/")) {
//	die(APP_STORAGE_PATH . "/import/new/ is not writeable by the webserver.  Please adjust permissions and reload this page.");
//}
//
//if(!is_writeable(APP_STORAGE_PATH . "/attachments/")) {
//	die(APP_STORAGE_PATH . "/attachments/ is not writeable by the webserver.  Please adjust permissions and reload this page.");
//}

// [TODO] Move this to the framework init (installer blocks this at the moment)
DevblocksPlatform::setLocale('en_US');

// Get a reference to the template system and configure it
$tpl = DevblocksPlatform::getTemplateService();
$tpl->template_dir = APP_PATH . '/install/templates';
$tpl->caching = 0;

$tpl->assign('application_name', INSTALL_APP_NAME);
$tpl->assign('step', $step);

switch($step) {
	// [TODO] Check server + php environment (extensions + php.ini)
	default:
	case STEP_ENVIRONMENT:
		$results = array();
		$fails = 0;
		
		// PHP Version
		if(version_compare(PHP_VERSION,"5.2") >=0) {
			$results['php_version'] = PHP_VERSION;
		} else {
			$results['php_version'] = false;
			$fails++;
		}
		
		// File Uploads
		$ini_file_uploads = ini_get("file_uploads");
		if($ini_file_uploads == 1 || strcasecmp($ini_file_uploads,"on")==0) {
			$results['file_uploads'] = true;
		} else {
			$results['file_uploads'] = false;
			$fails++;
		}
		
		// File Upload Temporary Directory
		$ini_upload_tmp_dir = ini_get("upload_tmp_dir");
		if(!empty($ini_upload_tmp_dir)) {
			$results['upload_tmp_dir'] = true;
		} else {
			$results['upload_tmp_dir'] = false;
			//$fails++; // Not fatal
		}

		$memory_limit = ini_get("memory_limit");
		if ($memory_limit == '') { // empty string means failure or not defined, assume no compiled memory limits
			$results['memory_limit'] = true;
		} else {
			$ini_memory_limit = intval($memory_limit);
			if($ini_memory_limit >= 16) {
				$results['memory_limit'] = true;
			} else {
				$results['memory_limit'] = false;
				$fails++;
			}
		}
		
		// Extension: Sessions
		if(extension_loaded("session")) {
			$results['ext_session'] = true;
		} else {
			$results['ext_session'] = false;
			$fails++;
		}
		
		// Extension: PCRE
		if(extension_loaded("pcre")) {
			$results['ext_pcre'] = true;
		} else {
			$results['ext_pcre'] = false;
			$fails++;
		}

		// Extension: SPL
		if(extension_loaded("spl")) {
			$results['ext_spl'] = true;
		} else {
			$results['ext_spl'] = false;
			$fails++;
		}

		// Extension: GD
		if(extension_loaded("gd") && function_exists('imagettfbbox')) {
			$results['ext_gd'] = true;
		} else {
			$results['ext_gd'] = false;
			$fails++;
		}
		
		// Extension: IMAP
		if(extension_loaded("imap")) {
			$results['ext_imap'] = true;
		} else {
			$results['ext_imap'] = false;
			$fails++;
		}
		
		// Extension: MailParse
//		if(extension_loaded("mailparse")) {
//			$results['ext_mailparse'] = true;
//		} else {
//			$results['ext_mailparse'] = false;
//			$fails++;
//		}
		
		// Extension: mbstring
		if(extension_loaded("mbstring")) {
			$results['ext_mbstring'] = true;
		} else {
			$results['ext_mbstring'] = false;
			$fails++;
		}
		
		// Extension: DOM
		if(extension_loaded("dom")) {
			$results['ext_dom'] = true;
		} else {
			$results['ext_dom'] = false;
			$fails++;
		}
		
		// Extension: cURL
		if(extension_loaded("curl")) {
			$results['ext_curl'] = true;
		} else {
			$results['ext_curl'] = false;
			$fails++;
		}
		
		// Extension: XML
		if(extension_loaded("xml")) {
			$results['ext_xml'] = true;
		} else {
			$results['ext_xml'] = false;
			$fails++;
		}
		
		// Extension: SimpleXML
		if(extension_loaded("simplexml")) {
			$results['ext_simplexml'] = true;
		} else {
			$results['ext_simplexml'] = false;
			$fails++;
		}
		
		// Extension: JSON
		if(extension_loaded("json")) {
			$results['ext_json'] = true;
		} else {
			$results['ext_json'] = false;
			$fails++;
		}
		
		$tpl->assign('fails', $fails);
		$tpl->assign('results', $results);
		$tpl->assign('template', 'steps/step_environment.tpl');
		
		break;
	
	case STEP_LICENSE:
	    @$accept = DevblocksPlatform::importGPC($_POST['accept'],'integer', 0);
	    
	    if(1 == $accept) {
			$tpl->assign('step', STEP_DATABASE);
			$tpl->display('steps/redirect.tpl');
			exit;
	    }
		
		$tpl->assign('template', 'steps/step_license.tpl');
		
	    break;	
	
	// Configure and test the database connection
	// [TODO] This should also patch in app_id + revision order
	// [TODO] This should remind the user to make a backup (and refer to a wiki article how)
	case STEP_DATABASE:
		// Import scope (if post)
		@$db_driver = DevblocksPlatform::importGPC($_POST['db_driver'],'string');
		@$db_encoding = DevblocksPlatform::importGPC($_POST['db_encoding'],'string');
		@$db_server = DevblocksPlatform::importGPC($_POST['db_server'],'string');
		@$db_name = DevblocksPlatform::importGPC($_POST['db_name'],'string');
		@$db_user = DevblocksPlatform::importGPC($_POST['db_user'],'string');
		@$db_pass = DevblocksPlatform::importGPC($_POST['db_pass'],'string');

		@$db = DevblocksPlatform::getDatabaseService();
		if(!is_null($db) && @$db->IsConnected()) {
			// If we've been to this step, skip past framework.config.php
			$tpl->assign('step', STEP_INIT_DB);
			$tpl->display('steps/redirect.tpl');
			exit;
		}
		unset($db);
		
		// [JAS]: Detect available database drivers
		
		$drivers = array();
		
		if(extension_loaded('mysql')) {
			$drivers['mysql'] = 'MySQL 3.23/4.x/5.x';
		}
		
		$tpl->assign('drivers', $drivers);
		
		if(!empty($db_driver) && !empty($db_server) && !empty($db_name) && !empty($db_user)) {
			// Test the given settings, bypass platform initially
			include_once(DEVBLOCKS_PATH . "libs/adodb5/adodb.inc.php");
			$ADODB_CACHE_DIR = APP_TEMP_PATH . "/cache";
			@$db =& ADONewConnection($db_driver);
			@$db->Connect($db_server, $db_user, $db_pass, $db_name);

			$tpl->assign('db_driver', $db_driver);
			$tpl->assign('db_server', $db_server);
			$tpl->assign('db_name', $db_name);
			$tpl->assign('db_user', $db_user);
			$tpl->assign('db_pass', $db_pass);
			
			// If passed, write config file and continue
			if(!is_null($db) && $db->IsConnected()) {
				$info = $db->GetRow("SHOW VARIABLES LIKE 'character_set_database'");
				
				$encoding = (0==strcasecmp($info[1],'utf8')) ? 'utf8' : 'latin1';
				
				// Write database settings to framework.config.php
				$result = DevblocksInstaller::saveFrameworkConfig($db_driver, $encoding, $db_server, $db_name, $db_user, $db_pass);
				
				// [JAS]: If we didn't save directly to the config file, user action required
				if(0 != strcasecmp($result,'config')) {
					$tpl->assign('result', $result);
					$tpl->assign('config_path', APP_PATH . "/framework.config.php");
					$tpl->assign('template', 'steps/step_config_file.tpl');
					
				} else { // skip the config writing step
					$tpl->assign('step', STEP_INIT_DB);
					$tpl->display('steps/redirect.tpl');
					exit;
				}
				
			} else { // If failed, re-enter
				$tpl->assign('failed', true);
				$tpl->assign('template', 'steps/step_database.tpl');
			}
			
		} else {
			$tpl->assign('db_server', 'localhost');
			$tpl->assign('template', 'steps/step_database.tpl');
		}
		break;
		
	// [JAS]: If we didn't save directly to the config file, user action required		
	case STEP_SAVE_CONFIG_FILE:
		@$db_driver = DevblocksPlatform::importGPC($_POST['db_driver'],'string');
		@$db_server = DevblocksPlatform::importGPC($_POST['db_server'],'string');
		@$db_name = DevblocksPlatform::importGPC($_POST['db_name'],'string');
		@$db_user = DevblocksPlatform::importGPC($_POST['db_user'],'string');
		@$db_pass = DevblocksPlatform::importGPC($_POST['db_pass'],'string');
		@$result = DevblocksPlatform::importGPC($_POST['result'],'string');
		
		// Check to make sure our constants match our input
		if(
			0 == strcasecmp($db_driver,APP_DB_DRIVER) &&
			0 == strcasecmp($db_server,APP_DB_HOST) &&
			0 == strcasecmp($db_name,APP_DB_DATABASE) &&
			0 == strcasecmp($db_user,APP_DB_USER) &&
			0 == strcasecmp($db_pass,APP_DB_PASS)
		) { // we did it!
			$tpl->assign('step', STEP_INIT_DB);
			$tpl->display('steps/redirect.tpl');
			exit;
			
		} else { // oops!
			$tpl->assign('db_driver', $db_driver);
			$tpl->assign('db_server', $db_server);
			$tpl->assign('db_name', $db_name);
			$tpl->assign('db_user', $db_user);
			$tpl->assign('db_pass', $db_pass);
			$tpl->assign('failed', true);
			$tpl->assign('result', $result);
			$tpl->assign('config_path', APP_PATH . "/framework.config.php");
			
			$tpl->assign('template', 'steps/step_config_file.tpl');
		}
		
		break;

	// Initialize the database
	case STEP_INIT_DB:
		// [TODO] Add current user to patcher/upgrade authorized IPs
		
		if(DevblocksPlatform::isDatabaseEmpty()) { // install
			$patchMgr = DevblocksPlatform::getPatchService();
			
			// [JAS]: Run our overloaded container for the platform
			$patchMgr->registerPatchContainer(new PlatformPatchContainer());
			
			// Clean script
			if(!$patchMgr->run()) {
				$tpl->assign('template', 'steps/step_init_db.tpl');
				
			} else { // success
				// Read in plugin information from the filesystem to the database
				DevblocksPlatform::readPlugins();
				
				/*
				 * [TODO] This possibly needs to only start with core, because as soon 
				 * as we add back another feature with licensing we'll have installer 
				 * errors trying to license plugins before core runs its DB install.
				 */
				$plugins = DevblocksPlatform::getPluginRegistry();
				
				// Tailor which plugins are enabled by default
				if(is_array($plugins))
				foreach($plugins as $plugin_manifest) { /* @var $plugin_manifest DevblocksPluginManifest */
					switch ($plugin_manifest->id) {
						case "portsensor.core":
						case "portsensor.webapi":
							$plugin_manifest->setEnabled(true);
							break;
						
						default:
							$plugin_manifest->setEnabled(false);
							break;
					}
				}
				
				DevblocksPlatform::clearCache();
				
				// Run enabled plugin patches
				$patches = DevblocksPlatform::getExtensions("devblocks.patch.container",false,true);
				
				if(is_array($patches))
				foreach($patches as $patch_manifest) { /* @var $patch_manifest DevblocksExtensionManifest */ 
					 $container = $patch_manifest->createInstance(); /* @var $container DevblocksPatchContainerExtension */
					 $patchMgr->registerPatchContainer($container);
				}
				
				if(!$patchMgr->run()) { // fail
					$tpl->assign('template', 'steps/step_init_db.tpl');
					
				} else {
					// Reload plugin translations
					DAO_Translation::reloadPluginStrings();
					
					// success
					$tpl->assign('step', STEP_CONTACT);
					$tpl->display('steps/redirect.tpl');
					exit;
				}
			
				// [TODO] Verify the database
			}
			
			
		} else { // upgrade / patch
			/*
			 * [TODO] We should probably only forward to upgrade when we know 
			 * the proper tables were installed.  We may be repeating an install 
			 * request where the clean DB failed.
			 */
			$tpl->assign('step', STEP_UPGRADE);
			$tpl->display('steps/redirect.tpl');
			exit;
		}
			
		break;
		

	// Personalize system information (title, timezone, language)
	case STEP_CONTACT:
		$settings = DevblocksPlatform::getPluginSettingsService();
		
		@$default_reply_from = DevblocksPlatform::importGPC($_POST['default_reply_from'],'string',$settings->get('portsensor.core',PortSensorSettings::DEFAULT_REPLY_FROM));
		@$default_reply_personal = DevblocksPlatform::importGPC($_POST['default_reply_personal'],'string',$settings->get('portsensor.core',PortSensorSettings::DEFAULT_REPLY_PERSONAL));
		@$app_title = DevblocksPlatform::importGPC($_POST['app_title'],'string',$settings->get('portsensor.core',PortSensorSettings::APP_TITLE,'PortSensor - Monitor Everything'));
		@$form_submit = DevblocksPlatform::importGPC($_POST['form_submit'],'integer');
		
		if(!empty($form_submit)) { // && !empty($default_reply_from)
			
			$validate = imap_rfc822_parse_adrlist(sprintf("<%s>", $default_reply_from),"localhost");
			
			if(!empty($default_reply_from) && is_array($validate) && 1==count($validate)) {
				$settings->set('portsensor.core',PortSensorSettings::DEFAULT_REPLY_FROM, $default_reply_from);
			}
			
			if(!empty($default_reply_personal)) {
				$settings->set('portsensor.core',PortSensorSettings::DEFAULT_REPLY_PERSONAL, $default_reply_personal);
			}
			
			if(!empty($app_title)) {
				$settings->set('portsensor.core',PortSensorSettings::APP_TITLE, $app_title);
			}
			
			$tpl->assign('step', STEP_OUTGOING_MAIL);
			$tpl->display('steps/redirect.tpl');
			exit;
		}
		
		if(!empty($form_submit) && empty($default_reply_from)) {
			$tpl->assign('failed', true);
		}
		
		$tpl->assign('default_reply_from', $default_reply_from);
		$tpl->assign('default_reply_personal', $default_reply_personal);
		$tpl->assign('app_title', $app_title);
		
		$tpl->assign('template', 'steps/step_contact.tpl');
		
		break;
	
	// Set up and test the outgoing SMTP
	case STEP_OUTGOING_MAIL:
		$settings = DevblocksPlatform::getPluginSettingsService();
		
		@$smtp_host = DevblocksPlatform::importGPC($_POST['smtp_host'],'string',$settings->get('portsensor.core',PortSensorSettings::SMTP_HOST,'localhost'));
		@$smtp_port = DevblocksPlatform::importGPC($_POST['smtp_port'],'integer',$settings->get('portsensor.core',PortSensorSettings::SMTP_PORT,25));
		@$smtp_enc = DevblocksPlatform::importGPC($_POST['smtp_enc'],'string',$settings->get('portsensor.core',PortSensorSettings::SMTP_ENCRYPTION_TYPE,'None'));
		@$smtp_auth_user = DevblocksPlatform::importGPC($_POST['smtp_auth_user'],'string');
		@$smtp_auth_pass = DevblocksPlatform::importGPC($_POST['smtp_auth_pass'],'string');
		@$form_submit = DevblocksPlatform::importGPC($_POST['form_submit'],'integer');
		@$passed = DevblocksPlatform::importGPC($_POST['passed'],'integer');
		
		if(!empty($form_submit)) {
			$mail_service = DevblocksPlatform::getMailService();
			
			$mailer = null;
			try {
				$mailer = $mail_service->getMailer(array(
					'host' => $smtp_host,
					'port' => $smtp_port,
					'auth_user' => $smtp_auth_user,
					'auth_pass' => $smtp_auth_pass,
					'enc' => $smtp_enc,
				));
				
				$transport = $mailer->getTransport();
				$transport->start();
				$transport->stop();
				
				if(!empty($smtp_host))
					$settings->set('portsensor.core',PortSensorSettings::SMTP_HOST, $smtp_host);
				if(!empty($smtp_port))
					$settings->set('portsensor.core',PortSensorSettings::SMTP_PORT, $smtp_port);
				if(!empty($smtp_auth_user)) {
					$settings->set('portsensor.core',PortSensorSettings::SMTP_AUTH_ENABLED, 1);
					$settings->set('portsensor.core',PortSensorSettings::SMTP_AUTH_USER, $smtp_auth_user);
					$settings->set('portsensor.core',PortSensorSettings::SMTP_AUTH_PASS, $smtp_auth_pass);
				} else {
					$settings->set('portsensor.core',PortSensorSettings::SMTP_AUTH_ENABLED, 0);
				}
				if(!empty($smtp_enc))
					$settings->set('portsensor.core',PortSensorSettings::SMTP_ENCRYPTION_TYPE, $smtp_enc);
				
				$tpl->assign('step', STEP_DEFAULTS);
				$tpl->display('steps/redirect.tpl');
				exit;
				
			}
			catch(Exception $e) {
				$form_submit = 0;
				$tpl->assign('smtp_error_display', 'SMTP Connection Failed! ' . $e->getMessage());
			}
			$tpl->assign('smtp_host', $smtp_host);
			$tpl->assign('smtp_port', $smtp_port);
			$tpl->assign('smtp_auth_user', $smtp_auth_user);
			$tpl->assign('smtp_auth_pass', $smtp_auth_pass);
			$tpl->assign('smtp_enc', $smtp_enc);
			$tpl->assign('form_submit', $form_submit);
		} else {
			$tpl->assign('smtp_host', 'localhost');
			$tpl->assign('smtp_port', '25');
			$tpl->assign('smtp_enc', 'None');
		}
		
		// First time, or retry
		$tpl->assign('template', 'steps/step_outgoing_mail.tpl');
		
		break;

	// Set up the default objects
	case STEP_DEFAULTS:
		@$form_submit = DevblocksPlatform::importGPC($_POST['form_submit'],'integer');
		@$worker_email = DevblocksPlatform::importGPC($_POST['worker_email'],'string');
		@$worker_pass = DevblocksPlatform::importGPC($_POST['worker_pass'],'string');
		@$worker_pass2 = DevblocksPlatform::importGPC($_POST['worker_pass2'],'string');

		$settings = DevblocksPlatform::getPluginSettingsService();
		$db = DevblocksPlatform::getDatabaseService();
		
		if(!empty($form_submit)) {
			// Persist form scope
			$tpl->assign('worker_email', $worker_email);
			$tpl->assign('worker_pass', $worker_pass);
			$tpl->assign('worker_pass2', $worker_pass2);
			
			// Sanity/Error checking
			if(!empty($worker_email) && !empty($worker_pass) && $worker_pass == $worker_pass2) {
				// If we have no groups, make a Dispatch group
//				$groups = DAO_Group::getAll(true);
//				if(empty($groups)) {
//					// Dispatch Group
//					$dispatch_gid = DAO_Group::createTeam(array(
//						DAO_Group::TEAM_NAME => 'Dispatch',
//					));
//					
//					// Dispatch Spam Bucket
//					$dispatch_spam_bid = DAO_Bucket::create('Spam', $dispatch_gid);
//					DAO_GroupSettings::set($dispatch_gid,DAO_GroupSettings::SETTING_SPAM_ACTION,'2');
//					DAO_GroupSettings::set($dispatch_gid,DAO_GroupSettings::SETTING_SPAM_ACTION_PARAM,$dispatch_spam_bid);
//					DAO_GroupSettings::set($dispatch_gid,DAO_GroupSettings::SETTING_SPAM_THRESHOLD,'85');
//					
//					// Support Group
//					$support_gid = DAO_Group::createTeam(array(
//						DAO_Group::TEAM_NAME => 'Support',
//					));
//
//					// Support Spam Bucket
//					$support_spam_bid = DAO_Bucket::create('Spam', $support_gid);
//					DAO_GroupSettings::set($support_gid,DAO_GroupSettings::SETTING_SPAM_ACTION,'2');
//					DAO_GroupSettings::set($support_gid,DAO_GroupSettings::SETTING_SPAM_ACTION_PARAM,$support_spam_bid);
//					DAO_GroupSettings::set($support_gid,DAO_GroupSettings::SETTING_SPAM_THRESHOLD,'85');
//					
//					// Sales Group
//					$sales_gid = DAO_Group::createTeam(array(
//						DAO_Group::TEAM_NAME => 'Sales',
//					));
//					
//					// Sales Spam Bucket
//					$sales_spam_bid = DAO_Bucket::create('Spam', $sales_gid);
//					DAO_GroupSettings::set($sales_gid,DAO_GroupSettings::SETTING_SPAM_ACTION,'2');
//					DAO_GroupSettings::set($sales_gid,DAO_GroupSettings::SETTING_SPAM_ACTION_PARAM,$sales_spam_bid);
//					DAO_GroupSettings::set($sales_gid,DAO_GroupSettings::SETTING_SPAM_THRESHOLD,'85');
//					
//					// Default catchall
//					DAO_Group::updateTeam($dispatch_gid, array(
//						DAO_Group::IS_DEFAULT => 1
//					));
//				}
				
				// If this worker doesn't exist, create them
				$results = DAO_Worker::getWhere(sprintf("%s = %s",
					DAO_Worker::EMAIL,
					$db->qstr($worker_email)
				));
				
				if(empty($results)) {
					$fields = array(
						DAO_Worker::EMAIL => $worker_email,
						DAO_Worker::PASS => md5($worker_pass),
						DAO_Worker::FIRST_NAME => 'Super',
						DAO_Worker::LAST_NAME => 'User',
						DAO_Worker::TITLE => 'Administrator',
						DAO_Worker::IS_SUPERUSER => 1,
					);
					
					$worker_id = DAO_Worker::create($fields);
					
//					// Default group memberships
//					if(!empty($dispatch_gid))
//						DAO_Group::setTeamMember($dispatch_gid,$worker_id,true);			
//					if(!empty($support_gid))
//						DAO_Group::setTeamMember($support_gid,$worker_id,true);			
//					if(!empty($sales_gid))
//						DAO_Group::setTeamMember($sales_gid,$worker_id,true);			
				}
				
				$tpl->assign('step', STEP_REGISTER);
				$tpl->display('steps/redirect.tpl');
				exit;
				
			} else {
				$tpl->assign('failed', true);
				
			}
			
		} else {
			// Defaults
			
		}
		
		$tpl->assign('template', 'steps/step_defaults.tpl');
		
		break;
		
	case STEP_REGISTER:
		@$form_submit = DevblocksPlatform::importGPC($_POST['form_submit'],'integer');
		@$skip = DevblocksPlatform::importGPC($_POST['skip'],'integer',0);
		
		if(!empty($form_submit)) {
			@$contact_name = str_replace(array("\r","\n"),'',stripslashes($_REQUEST['contact_name']));
			@$contact_email = str_replace(array("\r","\n"),'',stripslashes($_REQUEST['contact_email']));
			@$contact_company = stripslashes($_REQUEST['contact_company']);
			
			if(empty($skip) && !empty($contact_name)) {
				$settings = DevblocksPlatform::getPluginSettingsService();
				@$default_from = $settings->get('portsensor.core',PortSensorSettings::DEFAULT_REPLY_FROM,'');
				
				@$contact_phone = stripslashes($_REQUEST['contact_phone']);
				@$contact_refer = stripslashes($_REQUEST['contact_refer']);
				@$q1 = stripslashes($_REQUEST['q1']);
				@$q2 = stripslashes($_REQUEST['q2']);
				@$q3 = stripslashes($_REQUEST['q3']);
				@$q4 = stripslashes($_REQUEST['q4']);
				@$q5_support = stripslashes($_REQUEST['q5_support']);
				@$q5_opensource = stripslashes($_REQUEST['q5_opensource']);
				@$q5_price = stripslashes($_REQUEST['q5_price']);
				@$q5_updates = stripslashes($_REQUEST['q5_updates']);
				@$q5_developers = stripslashes($_REQUEST['q5_developers']);
				@$q5_community = stripslashes($_REQUEST['q5_community']);
				@$comments = stripslashes($_REQUEST['comments']);
				
				if(isset($_REQUEST['form_submit'])) {
				  $msg = sprintf(
				    "Contact Name: %s\r\n".
				    "Organization: %s\r\n".
				    "Referred by: %s\r\n".
				    "Phone: %s\r\n".
				    "\r\n".
				    "#1: Briefly, what does your organization do?\r\n%s\r\n\r\n".
				    "#2: How is your team currently handling service monitoring?\r\n%s\r\n\r\n".
				    "#3: Are you considering both free and commercial solutions?\r\n%s\r\n\r\n".
				    "#4: What will be your first important milestone?\r\n%s\r\n\r\n".
				    "#5: How important are the following benefits in making your decision?\r\n".
				    "Near-Instant Support: %d\r\nAvailable Source Code: %d\r\nCompetitive Purchase Price: %d\r\n".
				    "Frequent Product Updates: %d\r\nAccess to Developers: %d\r\nLarge User Community: %d\r\n".
				    "\r\n".
				    "Additional Comments: \r\n%s\r\n\r\n"
				    ,
				    $contact_name,
				    $contact_company,
				    $contact_refer,
				    $contact_phone,
				    $q1,
				    $q2,
				    $q3,
				    $q4,
				    $q5_support,
				    $q5_opensource,
				    $q5_price,
				    $q5_updates,
				    $q5_developers,
				    $q5_community,
				    $comments
				  );

				  PortSensorMail::quickSend('sales@portsensor.com',"About: $contact_name of $contact_company",$msg, $contact_email, $contact_name);
				}
			}
			
			$tpl->assign('step', STEP_FINISHED);
			$tpl->display('steps/redirect.tpl');
			exit;
		}
		
		$tpl->assign('template', 'steps/step_register.tpl');
		break;
		
	case STEP_UPGRADE:
		$tpl->assign('template', 'steps/step_upgrade.tpl');
		break;
		
	// [TODO] Delete the /install/ directory (security)
	case STEP_FINISHED:
		
		// Set up the default cron jobs
		$crons = DevblocksPlatform::getExtensions('portsensor.cron', true, true);
		if(is_array($crons))
		foreach($crons as $id => $cron) { /* @var $cron PortSensorCronExtension */
			switch($id) {
				case 'cron.maint':
					$cron->setParam(PortSensorCronExtension::PARAM_ENABLED, true);
					$cron->setParam(PortSensorCronExtension::PARAM_DURATION, '24');
					$cron->setParam(PortSensorCronExtension::PARAM_TERM, 'h');
					$cron->setParam(PortSensorCronExtension::PARAM_LASTRUN, strtotime('Yesterday'));
					break;
				case 'cron.heartbeat':
					$cron->setParam(PortSensorCronExtension::PARAM_ENABLED, true);
					$cron->setParam(PortSensorCronExtension::PARAM_DURATION, '5');
					$cron->setParam(PortSensorCronExtension::PARAM_TERM, 'm');
					$cron->setParam(PortSensorCronExtension::PARAM_LASTRUN, strtotime('Yesterday'));
					break;
				case 'cron.sensors':
					$cron->setParam(PortSensorCronExtension::PARAM_ENABLED, true);
					$cron->setParam(PortSensorCronExtension::PARAM_DURATION, '1');
					$cron->setParam(PortSensorCronExtension::PARAM_TERM, 'm');
					$cron->setParam(PortSensorCronExtension::PARAM_LASTRUN, strtotime('Yesterday'));
					break;
				case 'cron.alerts':
					$cron->setParam(PortSensorCronExtension::PARAM_ENABLED, true);
					$cron->setParam(PortSensorCronExtension::PARAM_DURATION, '1');
					$cron->setParam(PortSensorCronExtension::PARAM_TERM, 'm');
					$cron->setParam(PortSensorCronExtension::PARAM_LASTRUN, strtotime('Yesterday'));
					break;
			}
			
		}
		
		$tpl->assign('template', 'steps/step_finished.tpl');
		break;
}

// [TODO] Check apache rewrite (somehow)

// [TODO] Check if safe_mode is disabled, and if so set our php.ini overrides in the framework.config.php rewrite

$tpl->display('base.tpl');

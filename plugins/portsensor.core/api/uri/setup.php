<?php
class PsSetupPage extends PortSensorPageExtension  {
	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
		parent::__construct($manifest);
	}
	
	// [TODO] Refactor to isAuthorized
	function isVisible() {
		$worker = PortSensorApplication::getActiveWorker();
		
		if(empty($worker)) {
			return false;
		} elseif($worker->is_superuser) {
			return true;
		}
	}
	
	function getActivity() {
	    return new Model_Activity('activity.setup');
	}
	
	function render() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);

		$worker = PortSensorApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}

		if(file_exists(APP_PATH . '/install/')) {
			$tpl->assign('install_dir_warning', true);
		}
		
		$tab_manifests = DevblocksPlatform::getExtensions('portsensor.config.tab', false);
		uasort($tab_manifests, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Selected tab
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		array_shift($stack); // setup
		$tab_selected = array_shift($stack);
		$tpl->assign('tab_selected', $tab_selected);
		
		// [TODO] check showTab* hooks for active_worker->is_superuser (no ajax bypass)
		
		$tpl->display('file:' . $this->_TPL_PATH . 'setup/index.tpl');
	}
	
	// Ajax
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_SetupTab) {
			$inst->showTab();
		}
	}
	
	// Post
	function saveTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_SetupTab) {
			$inst->saveTab();
		}
	}
	
	/*
	 * [TODO] Proxy any func requests to be handled by the tab directly, 
	 * instead of forcing tabs to implement controllers.  This should check 
	 * for the *Action() functions just as a handleRequest would
	 */
	function handleTabActionAction() {
		@$tab = DevblocksPlatform::importGPC($_REQUEST['tab'],'string','');
		@$action = DevblocksPlatform::importGPC($_REQUEST['action'],'string','');

		if(null != ($tab_mft = DevblocksPlatform::getExtension($tab)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_SetupTab) {
				if(method_exists($inst,$action.'Action')) {
					call_user_func(array(&$inst, $action.'Action'));
				}
		}
	}
	
	// Ajax
	function showTabSettingsAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);
		
		$license = PortSensorLicense::getInstance();
		$tpl->assign('license', $license);
		
		$db = DevblocksPlatform::getDatabaseService();
		$rs = $db->Execute("SHOW TABLE STATUS");

		$total_db_size = 0;
		$total_db_data = 0;
		$total_db_indexes = 0;
		$total_db_slack = 0;
		$total_file_size = 0;
		
		// [TODO] This would likely be helpful to the /debug controller
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$table_name = $rs->fields['Name'];
			$table_size_data = intval($rs->fields['Data_length']);
			$table_size_indexes = intval($rs->fields['Index_length']);
			$table_size_slack = intval($rs->fields['Data_free']);
			
			$total_db_size += $table_size_data + $table_size_indexes;
			$total_db_data += $table_size_data;
			$total_db_indexes += $table_size_indexes;
			$total_db_slack += $table_size_slack;
			
			$rs->MoveNext();
		}
		
//		$sql = "SELECT SUM(file_size) FROM attachment";
//		$total_file_size = intval($db->GetOne($sql));

		$tpl->assign('total_db_size', number_format($total_db_size/1048576,2));
		$tpl->assign('total_db_data', number_format($total_db_data/1048576,2));
		$tpl->assign('total_db_indexes', number_format($total_db_indexes/1048576,2));
		$tpl->assign('total_db_slack', number_format($total_db_slack/1048576,2));
//		$tpl->assign('total_file_size', number_format($total_file_size/1048576,2));
		
		$tpl->display('file:' . $this->_TPL_PATH . 'setup/tabs/settings/index.tpl');
	}
	
	// Post
	function saveTabSettingsAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$worker = PortSensorApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','settings')));
			return;
		}
		
	    @$title = DevblocksPlatform::importGPC($_POST['title'],'string','');
	    @$logo = DevblocksPlatform::importGPC($_POST['logo'],'string');
	    @$authorized_ips_str = DevblocksPlatform::importGPC($_POST['authorized_ips'],'string','');

	    if(empty($title))
	    	$title = 'PortSensor - Monitor Everything';
	    
	    $settings = PortSensorSettings::getInstance();
	    $settings->set(PortSensorSettings::APP_TITLE, $title);
	    $settings->set(PortSensorSettings::APP_LOGO_URL, $logo); // [TODO] Enforce some kind of max resolution?
	    $settings->set(PortSensorSettings::AUTHORIZED_IPS, $authorized_ips_str);
	    
	    DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','settings')));
	}
	
	// Ajax
	function showTabPluginsAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);
		
		// Auto synchronize when viewing Config->Extensions
        DevblocksPlatform::readPlugins();
		
		$plugins = DevblocksPlatform::getPluginRegistry();
		unset($plugins['portsensor.core']);
		$tpl->assign('plugins', $plugins);
		
//		$points = DevblocksPlatform::getExtensionPoints();
//		$tpl->assign('points', $points);
		
		$license = PortSensorLicense::getInstance();
		$tpl->assign('license', $license);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'setup/tabs/plugins/index.tpl');
	}
	
	function saveTabPluginsAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$worker = PortSensorApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		@$plugins_enabled = DevblocksPlatform::importGPC($_REQUEST['plugins_enabled'],'array');
		$pluginStack = DevblocksPlatform::getPluginRegistry();

		if(is_array($plugins_enabled))
		foreach($plugins_enabled as $plugin_id) {
			$plugin = $pluginStack[$plugin_id];
			$plugin->setEnabled(true);
			unset($pluginStack[$plugin_id]);
		}

		// [JAS]: Clear unchecked plugins
		foreach($pluginStack as $plugin) {
			// [JAS]: We can't force disable core here [TODO] Improve
			if($plugin->id=='portsensor.core') continue;
			$plugin->setEnabled(false);
		}

		DevblocksPlatform::clearCache();
		
		// Run any enabled plugin patches
		// [TODO] Should the platform do this automatically on enable in order?
		$patchMgr = DevblocksPlatform::getPatchService();
		$patches = DevblocksPlatform::getExtensions("devblocks.patch.container",false,true);
		
		if(is_array($patches))
		foreach($patches as $patch_manifest) { /* @var $patch_manifest DevblocksExtensionManifest */ 
			 $container = $patch_manifest->createInstance(); /* @var $container DevblocksPatchContainerExtension */
			 $patchMgr->registerPatchContainer($container);
		}
		
		if(!$patchMgr->run()) { // fail
			die("Failed updating plugins."); // [TODO] Make this more graceful
		}
		
        // Reload plugin translations
		DAO_Translation::reloadPluginStrings();
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('setup','plugins')));
	}	
	
	// Ajax
	function showTabWorkersAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);
		
		$workers = DAO_Worker::getAllWithDisabled();
		$tpl->assign('workers', $workers);

//		$teams = DAO_Group::getAll();
//		$tpl->assign('teams', $teams);
		
		$tpl->assign('response_uri', 'setup/workers');
		
		$defaults = new Ps_AbstractViewModel();
		$defaults->id = 'workers_cfg';
		$defaults->class_name = 'Ps_WorkerView';
		
		$view = Ps_AbstractViewLoader::getView($defaults->id, $defaults);
		$tpl->assign('view', $view);
		$tpl->assign('view_fields', Ps_WorkerView::getFields());
		$tpl->assign('view_searchable_fields', Ps_WorkerView::getSearchFields());
		
		$tpl->assign('license', PortSensorLicense::getInstance());
		
		$tpl->display('file:' . $this->_TPL_PATH . 'setup/tabs/workers/index.tpl');
	}
	
	function showWorkerPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);
		
		$tpl->assign('view_id', $view_id);
		
		$worker = DAO_Worker::get($id);
		$tpl->assign('worker', $worker);
		
//		$teams = DAO_Group::getAll();
//		$tpl->assign('teams', $teams);
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getBySource(PsCustomFieldSource_Worker::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		$custom_field_values = DAO_CustomFieldValue::getValuesBySourceIds(PsCustomFieldSource_Worker::ID, $id);
		if(isset($custom_field_values[$id]))
			$tpl->assign('custom_field_values', $custom_field_values[$id]);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'setup/tabs/workers/peek.tpl');		
	}
	
	function saveWorkerPeekAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = PortSensorApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser) {
			return;
		}
		
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer');
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		@$first_name = DevblocksPlatform::importGPC($_POST['first_name'],'string');
		@$last_name = DevblocksPlatform::importGPC($_POST['last_name'],'string');
		@$title = DevblocksPlatform::importGPC($_POST['title'],'string');
		@$email = DevblocksPlatform::importGPC($_POST['email'],'string');
		@$password = DevblocksPlatform::importGPC($_POST['password'],'string');
		@$is_superuser = DevblocksPlatform::importGPC($_POST['is_superuser'],'integer', 0);
		@$disabled = DevblocksPlatform::importGPC($_POST['is_disabled'],'integer',0);
//		@$group_ids = DevblocksPlatform::importGPC($_POST['group_ids'],'array');
//		@$group_roles = DevblocksPlatform::importGPC($_POST['group_roles'],'array');
		@$delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);

		// [TODO] The superuser set bit here needs to be protected by ACL
		
		if(empty($first_name)) $first_name = "Anonymous";
		
		if(!empty($id) && !empty($delete)) {
			// Can't delete or disable self
			if($active_worker->id != $id)
				DAO_Worker::delete($id);
			
		} else {
			if(empty($id) && null == DAO_Worker::getWhere(sprintf("%s=%s",DAO_Worker::EMAIL,Ps_ORMHelper::qstr($email)))) {
				$workers = DAO_Worker::getAll();
				$license = PortSensorLicense::getInstance();
				if ((!empty($license) && !empty($license['serial'])) || count($workers) < 3) {
					// Creating new worker.  If password is empty, email it to them
				    if(empty($password)) {
				    	$settings = PortSensorSettings::getInstance();
						$replyFrom = $settings->get(PortSensorSettings::DEFAULT_REPLY_FROM);
						$replyPersonal = $settings->get(PortSensorSettings::DEFAULT_REPLY_PERSONAL, '');
						$url = DevblocksPlatform::getUrlService();
				    	
						$password = PortSensorApplication::generatePassword(8);
				    	
//						try {
//					        $mail_service = DevblocksPlatform::getMailService();
//					        $mailer = $mail_service->getMailer(CerberusMail::getMailerDefaults());
//					        $mail = $mail_service->createMessage();
//					        
//							$mail->setTo(array($email => $first_name . ' ' . $last_name));
//							$mail->setFrom(array($replyFrom => $replyPersonal));
//					        $mail->setSubject('Your new helpdesk login information!');
//					        $mail->generateId();
//							
//							$headers = $mail->getHeaders();
//							
//					        $headers->addTextHeader('X-Mailer','Cerberus Helpdesk (Build '.APP_BUILD.')');
//					        
//						    $body = sprintf("Your new helpdesk login information is below:\r\n".
//								"\r\n".
//						        "URL: %s\r\n".
//						        "Login: %s\r\n".
//						        "Password: %s\r\n".
//						        "\r\n".
//						        "You should change your password from Preferences after logging in for the first time.\r\n".
//						        "\r\n",
//							        $url->write('',true),
//							        $email,
//							        $password
//						    );
//					        
//							$mail->setBody($body);
//	
//							if(!$mailer->send($mail)) {
//								throw new Exception('Password notification email failed to send.');
//							}
//						} catch (Exception $e) {
//							// [TODO] need to report to the admin when the password email doesn't send.  The try->catch
//							// will keep it from killing php, but the password will be empty and the user will never get an email.
//						}
				    }
					
				    $fields = array(
				    	DAO_Worker::EMAIL => $email,
				    	DAO_Worker::PASS => $password
				    );
				    
					$id = DAO_Worker::create($fields);
				}
			} // end create worker
		    
		    // Update
			$fields = array(
				DAO_Worker::FIRST_NAME => $first_name,
				DAO_Worker::LAST_NAME => $last_name,
				DAO_Worker::TITLE => $title,
				DAO_Worker::EMAIL => $email,
				DAO_Worker::IS_SUPERUSER => $is_superuser,
				DAO_Worker::IS_DISABLED => $disabled,
			);
			
			// if we're resetting the password
			if(!empty($password)) {
				$fields[DAO_Worker::PASS] = md5($password);
			}
			
			// Update worker
			DAO_Worker::update($id, $fields);
			
			// Update group memberships
//			if(is_array($group_ids) && is_array($group_roles))
//			foreach($group_ids as $idx => $group_id) {
//				if(empty($group_roles[$idx])) {
//					DAO_Group::unsetTeamMember($group_id, $id);
//				} else {
//					DAO_Group::setTeamMember($group_id, $id, (2==$group_roles[$idx]));
//				}
//			}

			// Add the worker e-mail to the addresses table
//			if(!empty($email))
//				DAO_Address::lookupAddress($email, true);
			
			// Addresses
//			if(null == DAO_AddressToWorker::getByAddress($email)) {
//				DAO_AddressToWorker::assign($email, $id);
//				DAO_AddressToWorker::update($email, array(
//					DAO_AddressToWorker::IS_CONFIRMED => 1
//				));
//			}
			
			// Custom field saves
			@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost(PsCustomFieldSource_Worker::ID, $id, $field_ids);
		}
		
		if(!empty($view_id)) {
			$view = Ps_AbstractViewLoader::getView($view_id);
			$view->render();
		}
		
		//DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','workers')));		
	}
	
	function showWorkersBulkPanelAction() {
		@$id_csv = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$path = $this->_TPL_PATH;
		$tpl->assign('path', $path);
		$tpl->assign('view_id', $view_id);

	    if(!empty($id_csv)) {
	        $ids = DevblocksPlatform::parseCsvString($id_csv);
	        $tpl->assign('ids', implode(',', $ids));
	    }
		
	    // Lists
//	    $lists = DAO_FeedbackList::getWhere();
//	    $tpl->assign('lists', $lists);
	    
		// Custom Fields
		$custom_fields = DAO_CustomField::getBySource(PsCustomFieldSource_Worker::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . $path . 'setup/tabs/workers/bulk.tpl');
	}
	
	function doWorkersBulkUpdateAction() {
		// Checked rows
	    @$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
		$ids = DevblocksPlatform::parseCsvString($ids_str);

		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
	    
	    // View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = Ps_AbstractViewLoader::getView($view_id);
		
		// Worker fields
		@$is_disabled = trim(DevblocksPlatform::importGPC($_POST['is_disabled'],'string',''));

		$do = array();
		
		// Do: Disabled
		if(0 != strlen($is_disabled))
			$do['is_disabled'] = $is_disabled;
			
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);
		
		$view->doBulkUpdate($filter, $do, $ids);
		
		$view->render();
		return;
	}
	
//	// Ajax
//	function showTabGroupsAction() {
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl->cache_lifetime = "0";
//		$tpl->assign('path', $this->_TPL_PATH);
//		
//		$workers = DAO_Worker::getAllActive();
//		$tpl->assign('workers', $workers);
//
//		$teams = DAO_Group::getAll();
//		$tpl->assign('teams', $teams);
//		
//		$tpl->assign('license',PortSensorLicense::getInstance());
//		
//		$tpl->display('file:' . $this->_TPL_PATH . 'setup/tabs/groups/index.tpl');
//	}
//	
//	// Ajax
//	function showTabMailAction() {
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl->cache_lifetime = "0";
//		$tpl->assign('path', $this->_TPL_PATH);
//		
//		$settings = PortSensorSettings::getInstance();
//		$mail_service = DevblocksPlatform::getMailService();
//		
//		$smtp_host = $settings->get(PortSensorSettings::SMTP_HOST,'');
//		$smtp_port = $settings->get(PortSensorSettings::SMTP_PORT,25);
//		$smtp_auth_enabled = $settings->get(PortSensorSettings::SMTP_AUTH_ENABLED,false);
//		if ($smtp_auth_enabled) {
//			$smtp_auth_user = $settings->get(PortSensorSettings::SMTP_AUTH_USER,'');
//			$smtp_auth_pass = $settings->get(PortSensorSettings::SMTP_AUTH_PASS,''); 
//		} else {
//			$smtp_auth_user = '';
//			$smtp_auth_pass = ''; 
//		}
//		$smtp_enc = $settings->get(PortSensorSettings::SMTP_ENCRYPTION_TYPE,'None');
//		$smtp_max_sends = $settings->get(PortSensorSettings::SMTP_MAX_SENDS,'20');
//		
//		$pop3_accounts = DAO_Mail::getPop3Accounts();
//		$tpl->assign('pop3_accounts', $pop3_accounts);
//		
//		$tpl->display('file:' . $this->_TPL_PATH . 'setup/tabs/mail/index.tpl');
//	}
//	
//	function getMailboxAction() {
//		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
//		
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl->cache_lifetime = "0";
//		$tpl->assign('path', $this->_TPL_PATH);
//		
//		if(!empty($id)) {
//			@$pop3 = DAO_Mail::getPop3Account($id);
//			$tpl->assign('pop3_account', $pop3);
//		}
//		
//		$tpl->display('file:' . $this->_TPL_PATH . 'setup/tabs/mail/edit_pop3_account.tpl');
//		
//		return;
//	}
//	
//	function saveMailboxAction() {
//		$translate = DevblocksPlatform::getTranslationService();
//		
//		$worker = PortSensorApplication::getActiveWorker();
//		if(!$worker || !$worker->is_superuser) {
//			echo $translate->_('common.access_denied');
//			return;
//		}
//		
//		if(DEMO_MODE) {
//			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','mail')));
//			return;
//		}
//		
//		@$id = DevblocksPlatform::importGPC($_POST['account_id'],'integer');
//		@$enabled = DevblocksPlatform::importGPC($_POST['pop3_enabled'],'integer',0);
//		@$nickname = DevblocksPlatform::importGPC($_POST['nickname'],'string');
//		@$protocol = DevblocksPlatform::importGPC($_POST['protocol'],'string');
//		@$host = DevblocksPlatform::importGPC($_POST['host'],'string');
//		@$username = DevblocksPlatform::importGPC($_POST['username'],'string');
//		@$password = DevblocksPlatform::importGPC($_POST['password'],'string');
//		@$port = DevblocksPlatform::importGPC($_POST['port'],'integer');
//		@$delete = DevblocksPlatform::importGPC($_POST['delete'],'integer');
//
//		if(empty($nickname))
//			$nickname = "POP3";
//		
//		// Defaults
//		if(empty($port)) {
//		    switch($protocol) {
//		        case 'pop3':
//		            $port = 110; 
//		            break;
//		        case 'pop3-ssl':
//		            $port = 995;
//		            break;
//		        case 'imap':
//		            $port = 143;
//		            break;
//		        case 'imap-ssl':
//		            $port = 993;
//		            break;
//		    }
//		}
//
//	    // [JAS]: [TODO] convert to field constants
//		$fields = array(
//		    'enabled' => $enabled,
//			'nickname' => $nickname,
//			'protocol' => $protocol,
//			'host' => $host,
//			'username' => $username,
//			'password' => $password,
//			'port' => $port
//		);
//		
//		if(!empty($id) && !empty($delete)) {
//			DAO_Mail::deletePop3Account($id);
//			
//		} elseif(!empty($id)) {
//			DAO_Mail::updatePop3Account($id, $fields);
//			
//		} else {
//            if(!empty($host) && !empty($username)) {
//			    $id = DAO_Mail::createPop3Account($fields);
//            }
//		}
//		
//		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','mail')));
//		
//		return;
//	}
//	
//	function getSmtpTestAction() {
//		$translate = DevblocksPlatform::getTranslationService();
//		
//		@$host = DevblocksPlatform::importGPC($_REQUEST['host'],'string','');
//		@$port = DevblocksPlatform::importGPC($_REQUEST['port'],'integer',25);
//		@$smtp_enc = DevblocksPlatform::importGPC($_REQUEST['enc'],'string','');
//		@$smtp_auth = DevblocksPlatform::importGPC($_REQUEST['smtp_auth'],'integer',0);
//		@$smtp_user = DevblocksPlatform::importGPC($_REQUEST['smtp_user'],'string','');
//		@$smtp_pass = DevblocksPlatform::importGPC($_REQUEST['smtp_pass'],'string','');
//		
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl->cache_lifetime = "0";
//		$tpl->assign('path', $this->_TPL_PATH);
//		
//		// [JAS]: Test the provided SMTP settings and give form feedback
//		if(!empty($host)) {
//			try {
//				$mail_service = DevblocksPlatform::getMailService();
//				$mailer = $mail_service->getMailer(array(
//					'host' => $host,
//					'port' => $port,
//					'auth_user' => $smtp_user,
//					'auth_pass' => $smtp_pass,
//					'enc' => $smtp_enc,
//				));
//				
//				$transport = $mailer->getTransport();
//				$transport->start();
//				$transport->stop();
//				$tpl->assign('smtp_test', true);
//				
//			} catch(Exception $e) {
//				$tpl->assign('smtp_test', false);
//				$tpl->assign('smtp_test_output', $translate->_('config.mail.smtp.failed') . ' ' . $e->getMessage());
//			}
//			
//			$tpl->display('file:' . $this->_TPL_PATH . 'setup/tabs/mail/test_smtp.tpl');			
//		}
//		
//		return;
//	}
//	
//	function getMailboxTestAction() {
//		$translate = DevblocksPlatform::getTranslationService();
//		
//		@$protocol = DevblocksPlatform::importGPC($_REQUEST['protocol'],'string','');
//		@$host = DevblocksPlatform::importGPC($_REQUEST['host'],'string','');
//		@$port = DevblocksPlatform::importGPC($_REQUEST['port'],'integer',110);
//		@$user = DevblocksPlatform::importGPC($_REQUEST['user'],'string','');
//		@$pass = DevblocksPlatform::importGPC($_REQUEST['pass'],'string','');
//		
//		// Defaults
//		if(empty($port)) {
//		    switch($protocol) {
//		        case 'pop3':
//		            $port = 110; 
//		            break;
//		        case 'pop3-ssl':
//		            $port = 995;
//		            break;
//		        case 'imap':
//		            $port = 143;
//		            break;
//		        case 'imap-ssl':
//		            $port = 993;
//		            break;
//		    }
//		}
//		
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl->cache_lifetime = "0";
//		$tpl->assign('path', $this->_TPL_PATH);
//		
//		// [JAS]: Test the provided POP settings and give form feedback
//		if(!empty($host)) {
//			$mail_service = DevblocksPlatform::getMailService();
//			
//			if(false !== $mail_service->testImap($host, $port, $protocol, $user, $pass)) {
//				$tpl->assign('pop_test', true);
//				
//			} else {
//				$tpl->assign('pop_test', false);
//				$tpl->assign('pop_test_output', $translate->_('config.mail.pop3.failed'));
//			}
//			
//		} else {
//			$tpl->assign('pop_test, false');
//			$tpl->assign('pop_test_output', $translate->_('config.mail.pop3.error_hostname'));
//		}
//		
//		$tpl->display('file:' . $this->_TPL_PATH . 'setup/tabs/mail/test_pop.tpl');
//		
//		return;
//	}
//	
//	// Ajax
//	function showTabPreParserAction() {
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl->cache_lifetime = "0";
//		$tpl->assign('path', $this->_TPL_PATH);
//		
//		$filters = DAO_PreParseRule::getAll(true);
//		$tpl->assign('filters', $filters);
//		
//		$groups = DAO_Group::getAll();
//		$tpl->assign('groups', $groups);
//		
//		// Custom Field Sources
//		$source_manifests = DevblocksPlatform::getExtensions('cerberusweb.fields.source', false);
//		$tpl->assign('source_manifests', $source_manifests);
//		
//		// Custom Fields
//		$custom_fields =  DAO_CustomField::getAll();
//		$tpl->assign('custom_fields', $custom_fields);
//
//		// Criteria extensions
//		$filter_criteria_exts = DevblocksPlatform::getExtensions('cerberusweb.mail_filter.criteria', false);
//		$tpl->assign('filter_criteria_exts', $filter_criteria_exts);
//		
//		// Action extensions
//		$filter_action_exts = DevblocksPlatform::getExtensions('cerberusweb.mail_filter.action', false);
//		$tpl->assign('filter_action_exts', $filter_action_exts);
//
//		$tpl->display('file:' . $this->_TPL_PATH . 'setup/tabs/mail/mail_preparse.tpl');
//	}
//
//	function saveTabPreParseFiltersAction() {
//		@$ids = DevblocksPlatform::importGPC($_POST['deletes'],'array',array());
//	    @$sticky_ids = DevblocksPlatform::importGPC($_REQUEST['sticky_ids'],'array',array());
//	    @$sticky_order = DevblocksPlatform::importGPC($_REQUEST['sticky_order'],'array',array());
//
//		if(DEMO_MODE) {
//			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','preparser')));
//			return;
//		}
//		
//		DAO_PreParseRule::delete($ids);
//
//	    // Reordering
//	    if(is_array($sticky_ids) && is_array($sticky_order))
//	    foreach($sticky_ids as $idx => $id) {
//	    	@$order = intval($sticky_order[$idx]);
//			DAO_PreParseRule::update($id, array (
//	    		DAO_PreParseRule::STICKY_ORDER => $order
//	    	));
//	    }
//		
//		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','preparser')));
//	}
//	
//	// Ajax
//	function showPreParserPanelAction() {
//		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
//		
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl->cache_lifetime = "0";
//		$tpl->assign('path', $this->_TPL_PATH);
//		
//		if(null != ($filter = DAO_PreParseRule::get($id))) {
//			$tpl->assign('filter', $filter);
//		}
//		
//		$groups = DAO_Group::getAll();
//		$tpl->assign('groups', $groups);
//
//		// Custom Fields: Address
//		$address_fields = DAO_CustomField::getBySource(PsCustomFieldSource_Address::ID);
//		$tpl->assign('address_fields', $address_fields);
//		
//		// Custom Fields: Orgs
//		$org_fields = DAO_CustomField::getBySource(PsCustomFieldSource_Org::ID);
//		$tpl->assign('org_fields', $org_fields);
//		
//		// Criteria extensions
//		$filter_criteria_exts = DevblocksPlatform::getExtensions('cerberusweb.mail_filter.criteria', true);
//		$tpl->assign('filter_criteria_exts', $filter_criteria_exts);
//		
//		// Action extensions
//		$filter_action_exts = DevblocksPlatform::getExtensions('cerberusweb.mail_filter.action', true);
//		$tpl->assign('filter_action_exts', $filter_action_exts);
//		
//		$tpl->display('file:' . $this->_TPL_PATH . 'setup/tabs/mail/preparser/peek.tpl');
//	}
//	
//	// Post
//	function saveTabPreParserAction() {
//		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer',0);
//		@$name = DevblocksPlatform::importGPC($_POST['name'],'string','');
//		@$is_sticky = DevblocksPlatform::importGPC($_POST['is_sticky'],'integer',0);
//		@$rules = DevblocksPlatform::importGPC($_POST['rules'],'array',array());
//		@$do = DevblocksPlatform::importGPC($_POST['do'],'array',array());
//		
//		if(DEMO_MODE) {
//			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','preparser')));
//			return;
//		}
//		
//		$criterion = array();
//		$actions = array();
//		
//		// Custom fields
//		$custom_fields = DAO_CustomField::getAll();
//		
//		// Criteria extensions
//		$filter_criteria_exts = DevblocksPlatform::getExtensions('cerberusweb.mail_filter.criteria', false);
//		
//		// Criteria
//		if(is_array($rules))
//		foreach($rules as $rule) {
//			$rule = DevblocksPlatform::strAlphaNumDash($rule);
//			@$value = DevblocksPlatform::importGPC($_POST['value_'.$rule],'string','');
//			
//			// [JAS]: Allow empty $value (null/blank checking)
//			
//			$criteria = array(
//				'value' => $value,
//			);
//			
//			// Any special rule handling
//			switch($rule) {
//				case 'dayofweek':
//					// days
//					$days = DevblocksPlatform::importGPC($_REQUEST['value_dayofweek'],'array',array());
//					if(in_array(0,$days)) $criteria['sun'] = 'Sunday';
//					if(in_array(1,$days)) $criteria['mon'] = 'Monday';
//					if(in_array(2,$days)) $criteria['tue'] = 'Tuesday';
//					if(in_array(3,$days)) $criteria['wed'] = 'Wednesday';
//					if(in_array(4,$days)) $criteria['thu'] = 'Thursday';
//					if(in_array(5,$days)) $criteria['fri'] = 'Friday';
//					if(in_array(6,$days)) $criteria['sat'] = 'Saturday';
//					unset($criteria['value']);
//					break;
//				case 'timeofday':
//					$from = DevblocksPlatform::importGPC($_REQUEST['timeofday_from'],'string','');
//					$to = DevblocksPlatform::importGPC($_REQUEST['timeofday_to'],'string','');
//					$criteria['from'] = $from;
//					$criteria['to'] = $to;
//					unset($criteria['value']);
//					break;
//				case 'type':
//					break;
//				case 'from':
//					break;
//				case 'tocc':
//					break;
//				case 'header1':
//				case 'header2':
//				case 'header3':
//				case 'header4':
//				case 'header5':
//					if(null != (@$header = DevblocksPlatform::importGPC($_POST[$rule],'string',null)))
//						$criteria['header'] = strtolower($header);
//					break;
//				case 'body':
//					break;
//				case 'body_encoding':
//					break;
//				case 'attachment':
//					break;
//				default: // ignore invalids
//					// Custom fields
//					if("cf_" == substr($rule,0,3)) {
//						$field_id = intval(substr($rule,3));
//						
//						if(!isset($custom_fields[$field_id]))
//							continue;
//
//						// [TODO] Operators
//							
//						switch($custom_fields[$field_id]->type) {
//							case 'S': // string
//							case 'T': // clob
//							case 'U': // URL
//								$oper = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_oper'],'string','regexp');
//								$criteria['oper'] = $oper;
//								break;
//							case 'D': // dropdown
//							case 'M': // multi-dropdown
//							case 'X': // multi-checkbox
//							case 'W': // worker
//								$in_array = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id],'array',array());
//								$out_array = array();
//								
//								// Hash key on the option for quick lookup later
//								if(is_array($in_array))
//								foreach($in_array as $k => $v) {
//									$out_array[$v] = $v;
//								}
//								
//								$criteria['value'] = $out_array;
//								break;
//							case 'E': // date
//								$from = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_from'],'string','0');
//								$to = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_to'],'string','now');
//								$criteria['from'] = $from;
//								$criteria['to'] = $to;
//								unset($criteria['value']);
//								break;
//							case 'N': // number
//								$oper = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_oper'],'string','=');
//								$criteria['oper'] = $oper;
//								$criteria['value'] = intval($value);
//								break;
//							case 'C': // checkbox
//								$criteria['value'] = intval($value);
//								break;
//						}
//						
//					} elseif(isset($filter_criteria_exts[$rule])) { // Extensions
//						// Save custom criteria properties
//						try {
//							$crit_ext = $filter_criteria_exts[$rule]->createInstance();
//							/* @var $crit_ext Extension_MailFilterCriteria */
//							$criteria = $crit_ext->saveConfig();
//						} catch(Exception $e) {
//							// print_r($e);
//						}
//					} else {
//						continue;
//					}
//					
//					break;
//			}
//			
//			$criterion[$rule] = $criteria;
//		}
//		
//		// Actions
//		$filter_action_exts = DevblocksPlatform::getExtensions('cerberusweb.mail_filter.action', false);
//
//		if(is_array($do))
//		foreach($do as $act) {
//			$action = array();
//			
//			switch($act) {
//				case 'stop':
//					if(null != (@$do_stop = DevblocksPlatform::importGPC($_POST['do_stop'],'string',null))) {
//						$act = $do_stop;
//						switch($do_stop) {
//							case 'nothing':
//								$action = array();
//								break;
//							case 'blackhole':
//								$action = array();
//								break;
//							case 'redirect':
//								if(null != (@$to = DevblocksPlatform::importGPC($_POST['do_redirect'],'string',null)))
//									$action = array(
//										'to' => $to
//									);
//								break;
//							case 'bounce':
//								if(null != (@$msg = DevblocksPlatform::importGPC($_POST['do_bounce'],'string',null)))
//									$action = array(
//										'message' => $msg
//									);
//								break;
//						}
//					}
//					break;
//					
//				default: // ignore invalids
//					// Check action plugins
//					if(isset($filter_action_exts[$act])) {
//						// Save custom action properties
//						try {
//							$action_ext = $filter_action_exts[$act]->createInstance();
//							$action = $action_ext->saveConfig();
//							
//						} catch(Exception $e) {
//							// print_r($e);
//						}
//					} else {
//						continue;
//					}
//					break;
//			}
//			
//			$actions[$act] = $action;
//		}
//		
//		if(!empty($criterion)) {
//			if(empty($id))  {
//				$fields = array(
//					DAO_PreParseRule::NAME => $name,
//					DAO_PreParseRule::CRITERIA_SER => serialize($criterion),
//					DAO_PreParseRule::ACTIONS_SER => serialize($actions),
//					DAO_PreParseRule::POS => 0,
//					DAO_PreParseRule::IS_STICKY => intval($is_sticky),
//				);
//				$id = DAO_PreParseRule::create($fields);
//			} else {
//				$fields = array(
//					DAO_PreParseRule::NAME => $name,
//					DAO_PreParseRule::CRITERIA_SER => serialize($criterion),
//					DAO_PreParseRule::ACTIONS_SER => serialize($actions),
//					DAO_PreParseRule::IS_STICKY => intval($is_sticky),
//				);
//				DAO_PreParseRule::update($id, $fields);
//			}
//		}
//		
//		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','preparser')));
//	}	
//	
//	// Ajax
//	function showTabParserAction() {
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl->cache_lifetime = "0";
//		$tpl->assign('path', $this->_TPL_PATH);
//
//		$rules = DAO_MailToGroupRule::getWhere();
//		$tpl->assign('rules', $rules);
//
//		$groups = DAO_Group::getAll();
//		$tpl->assign('groups', $groups);
//
////		$buckets = DAO_Bucket::getAll();
////		$tpl->assign('buckets', $buckets);
//		
//		// Custom Field Sources
//		$source_manifests = DevblocksPlatform::getExtensions('cerberusweb.fields.source', false);
//		$tpl->assign('source_manifests', $source_manifests);
//		
//		// Custom Fields
//		$custom_fields =  DAO_CustomField::getAll();
//		$tpl->assign('custom_fields', $custom_fields);
//		
//		$tpl->display('file:' . $this->_TPL_PATH . 'setup/tabs/mail/mail_routing.tpl');
//	}
//	
	
	// Ajax
//	function showTabPermissionsAction() {
//		$settings = PortSensorSettings::getInstance();
//		
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl->cache_lifetime = "0";
//		$tpl->assign('path', $this->_TPL_PATH);
//		
//		$license = PortSensorLicense::getInstance();
//		$tpl->assign('license', $license);	
//		
//		$plugins = DevblocksPlatform::getPluginRegistry();
//		$tpl->assign('plugins', $plugins);
//		
//		$acl = DevblocksPlatform::getAclRegistry();
//		$tpl->assign('acl', $acl);
//		
//		$roles = DAO_WorkerRole::getWhere();
//		$tpl->assign('roles', $roles);
//		
//		$workers = DAO_Worker::getAllActive();
//		$tpl->assign('workers', $workers);
//		
//		// Permissions enabled
//		$acl_enabled = $settings->get(PortSensorSettings::ACL_ENABLED);
//		$tpl->assign('acl_enabled', $acl_enabled);
//		
//		if(empty($license) || (!empty($license)&&isset($license['a'])))
//			$tpl->display('file:' . $this->_TPL_PATH . 'setup/tabs/acl/trial.tpl');
//		else
//			$tpl->display('file:' . $this->_TPL_PATH . 'setup/tabs/acl/index.tpl');
//	}
//	
//	function toggleACLAction() {
//		$worker = PortSensorApplication::getActiveWorker();
//		$settings = PortSensorSettings::getInstance();
//		
//		if(!$worker || !$worker->is_superuser) {
//			return;
//		}
//		
//		@$enabled = DevblocksPlatform::importGPC($_REQUEST['enabled'],'integer',0);
//		
//		$settings->set(PortSensorSettings::ACL_ENABLED, $enabled);
//	}
//	
//	function getRoleAction() {
//		$translate = DevblocksPlatform::getTranslationService();
//		$worker = PortSensorApplication::getActiveWorker();
//		
//		if(!$worker || !$worker->is_superuser) {
//			echo $translate->_('common.access_denied');
//			return;
//		}
//		
//		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
//
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl->cache_lifetime = "0";
//		$tpl->assign('path', $this->_TPL_PATH);
//
//		$plugins = DevblocksPlatform::getPluginRegistry();
//		$tpl->assign('plugins', $plugins);
//		
//		$acl = DevblocksPlatform::getAclRegistry();
//		$tpl->assign('acl', $acl);
//
//		$workers = DAO_Worker::getAllActive();
//		$tpl->assign('workers', $workers);
//		
//		$role = DAO_WorkerRole::get($id);
//		$tpl->assign('role', $role);
//		
//		$role_privs = DAO_WorkerRole::getRolePrivileges($id);
//		$tpl->assign('role_privs', $role_privs);
//		
//		$role_roster = DAO_WorkerRole::getRoleWorkers($id);
//		$tpl->assign('role_workers', $role_roster);
//		
//		$tpl->assign('license', PortSensorLicense::getInstance());
//		
//		$tpl->display('file:' . $this->_TPL_PATH . 'setup/tabs/acl/edit_role.tpl');
//	}
//	
//	// Post
//	function saveRoleAction() {
//		$translate = DevblocksPlatform::getTranslationService();
//		$worker = PortSensorApplication::getActiveWorker();
//		
//		if(!$worker || !$worker->is_superuser) {
//			echo $translate->_('common.access_denied');
//			return;
//		}
//		
//		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
//		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
//		@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_ids'],'array',array());
//		@$acl_privs = DevblocksPlatform::importGPC($_REQUEST['acl_privs'],'array',array());
//		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
//		
//		// Sanity checks
//		if(empty($name))
//			$name = 'New Role';
//		
//		// Delete
//		if(!empty($do_delete) && !empty($id)) {
//			DAO_WorkerRole::delete($id);
//			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','acl')));
//		}
//
//		$fields = array(
//			DAO_WorkerRole::NAME => $name,
//		);
//			
//		if(empty($id)) { // create
//			$id = DAO_WorkerRole::create($fields);
//					
//		} else { // edit
//			DAO_WorkerRole::update($id, $fields);
//		}
//
//		// Update role roster
//		DAO_WorkerRole::setRoleWorkers($id, $worker_ids);
//		
//		// Update role privs
//		DAO_WorkerRole::setRolePrivileges($id, $acl_privs, true);
//		
//		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','acl')));
//	}
	
	// Ajax
	function showTabSchedulerAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);
		
	    $jobs = DevblocksPlatform::getExtensions('portsensor.cron', true);
		$tpl->assign('jobs', $jobs);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'setup/tabs/scheduler/index.tpl');
	}
	
	// Post
	function saveTabSchedulerAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$worker = PortSensorApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','scheduler')));
			return;
		}
		
	    // [TODO] Save the job changes
	    @$id = DevblocksPlatform::importGPC($_REQUEST['id'],'string','');
	    @$enabled = DevblocksPlatform::importGPC($_REQUEST['enabled'],'integer',0);
	    @$locked = DevblocksPlatform::importGPC($_REQUEST['locked'],'integer',0);
	    @$duration = DevblocksPlatform::importGPC($_REQUEST['duration'],'integer',5);
	    @$term = DevblocksPlatform::importGPC($_REQUEST['term'],'string','m');
	    @$starting = DevblocksPlatform::importGPC($_REQUEST['starting'],'string','');
	    	    
	    $manifest = DevblocksPlatform::getExtension($id);
	    $job = $manifest->createInstance(); /* @var $job PortSensorCronExtension */

	    if(!empty($starting)) {
		    $starting_time = strtotime($starting);
		    if(false === $starting_time) $starting_time = time();
		    $starting_time -= PortSensorCronExtension::getIntervalAsSeconds($duration, $term);
    	    $job->setParam(PortSensorCronExtension::PARAM_LASTRUN, $starting_time);
	    }
	    
	    if(!$job instanceof PortSensorCronExtension)
	        die($translate->_('common.access_denied'));
	    
	    // [TODO] This is really kludgey
	    $job->setParam(PortSensorCronExtension::PARAM_ENABLED, $enabled);
	    $job->setParam(PortSensorCronExtension::PARAM_LOCKED, $locked);
	    $job->setParam(PortSensorCronExtension::PARAM_DURATION, $duration);
	    $job->setParam(PortSensorCronExtension::PARAM_TERM, $term);
	    
	    $job->saveConfigurationAction();
	    	    
	    DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','scheduler')));
	}	
	
	// Ajax
	function showTabFieldsAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);
		
		// Alphabetize
		$source_manifests = DevblocksPlatform::getExtensions('portsensor.fields.source', false);
		uasort($source_manifests, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
		$tpl->assign('source_manifests', $source_manifests);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'setup/tabs/fields/index.tpl');
	}
	
	private function _getFieldSource($ext_id) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);

		$tpl->assign('ext_id', $ext_id);

		// [TODO] Make sure the extension exists before continuing
		$source_manifest = DevblocksPlatform::getExtension($ext_id, false);
		$tpl->assign('source_manifest', $source_manifest);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);

		// Look up the defined global fields by the given extension
		$fields = DAO_CustomField::getBySource($ext_id);
		$tpl->assign('fields', $fields);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'setup/tabs/fields/edit_source.tpl');
	}
	
	// Ajax
	function getFieldSourceAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$worker = PortSensorApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id']);
		$this->_getFieldSource($ext_id);
	}
		
	// Post
	function saveFieldsAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$worker = PortSensorApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','fields')));
			return;
		}
		
		// Type of custom fields
		@$ext_id = DevblocksPlatform::importGPC($_POST['ext_id'],'string','');
		
		// Properties
		@$ids = DevblocksPlatform::importGPC($_POST['ids'],'array',array());
		@$names = DevblocksPlatform::importGPC($_POST['names'],'array',array());
		@$orders = DevblocksPlatform::importGPC($_POST['orders'],'array',array());
		@$options = DevblocksPlatform::importGPC($_POST['options'],'array',array());
		@$deletes = DevblocksPlatform::importGPC($_POST['deletes'],'array',array());
		
		if(!empty($ids) && !empty($ext_id))
		foreach($ids as $idx => $id) {
			@$name = $names[$idx];
			@$order = intval($orders[$idx]);
			@$option = $options[$idx];
			@$delete = (false !== array_search($id,$deletes) ? 1 : 0);
			
			if($delete) {
				DAO_CustomField::delete($id);
				
			} else {
				$fields = array(
					DAO_CustomField::NAME => $name, 
					DAO_CustomField::POS => $order, 
					DAO_CustomField::OPTIONS => !is_null($option) ? $option : '', 
				);
				DAO_CustomField::update($id, $fields);
			}
		}
		
		// Adding
		@$add_name = DevblocksPlatform::importGPC($_POST['add_name'],'string','');
		@$add_type = DevblocksPlatform::importGPC($_POST['add_type'],'string','');
		@$add_options = DevblocksPlatform::importGPC($_POST['add_options'],'string','');
		
		if(!empty($add_name) && !empty($add_type)) {
			$fields = array(
				DAO_CustomField::NAME => $add_name,
				DAO_CustomField::TYPE => $add_type,
				DAO_CustomField::SOURCE_EXTENSION => $ext_id,
				DAO_CustomField::OPTIONS => $add_options,
			);
			$id = DAO_CustomField::create($fields);
		}

		// Redraw the form
		$this->_getFieldSource($ext_id);
	}
	
//	// Post
//	function saveLicensesAction() {
//		$translate = DevblocksPlatform::getTranslationService();
//		$settings = PortSensorSettings::getInstance();
//		$worker = PortSensorApplication::getActiveWorker();
//		
//		if(!$worker || !$worker->is_superuser) {
//			echo $translate->_('common.access_denied');
//			return;
//		}
//		
//		@$key = DevblocksPlatform::importGPC($_POST['key'],'string','');
//		@$email = DevblocksPlatform::importGPC($_POST['email'],'string','');
//		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);
//
//		if(DEMO_MODE) {
//			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','settings')));
//			return;
//		}
//
//		if(!empty($do_delete)) {
//			$settings->set(PortSensorSettings::LICENSE, '');
//			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','settings')));
//			return;
//		}
//		
//		if(empty($key) || empty($email)) {
//			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','settings','empty')));
//			return;
//		}
//		
//		if(null==($valid = PortSensorLicense::validate($key,$email)) || 5!=count($valid)) {
//			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','settings','invalid')));
//			return;
//		}
//		
//		/*
//		 * [IMPORTANT -- Yes, this is simply a line in the sand.]
//		 * You're welcome to modify the code to meet your needs, but please respect 
//		 * our licensing.  Buy a legitimate copy to help support the project!
//		 * http://www.cerberusweb.com/
//		 */
//		$license = $valid;
//		
//		$settings->set(PortSensorSettings::LICENSE, serialize($license));
//		
//		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','settings')));
//	}
//	
//	// Ajax
//	function getTeamAction() {
//		$translate = DevblocksPlatform::getTranslationService();
//		$worker = PortSensorApplication::getActiveWorker();
//		
//		if(!$worker || !$worker->is_superuser) {
//			echo $translate->_('common.access_denied');
//			return;
//		}
//		
//		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
//
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl->cache_lifetime = "0";
//		$tpl->assign('path', $this->_TPL_PATH);
//
//		$teams = DAO_Group::getAll();
//		$tpl->assign('teams', $teams);
//		
//		@$team = $teams[$id];
//		$tpl->assign('team', $team);
//		
//		if(!empty($id)) {
//			@$members = DAO_Group::getTeamMembers($id);
//			$tpl->assign('members', $members);
//		}
//		
//		$workers = DAO_Worker::getAllActive();
//		$tpl->assign('workers', $workers);
//		
//		$tpl->assign('license',PortSensorLicense::getInstance());
//		
//		$tpl->display('file:' . $this->_TPL_PATH . 'setup/tabs/groups/edit_group.tpl');
//	}
//	
//	// Post
//	function saveTeamAction() {
//		$translate = DevblocksPlatform::getTranslationService();
//		$worker = PortSensorApplication::getActiveWorker();
//		
//		if(!$worker || !$worker->is_superuser) {
//			echo $translate->_('common.access_denied');
//			return;
//		}
//		
//		if(DEMO_MODE) {
//			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','workflow')));
//			return;
//		}
//		
//		@$id = DevblocksPlatform::importGPC($_POST['id']);
//		@$name = DevblocksPlatform::importGPC($_POST['name']);
//		@$delete = DevblocksPlatform::importGPC($_POST['delete_box']);
//		@$delete_move_id = DevblocksPlatform::importGPC($_POST['delete_move_id'],'integer',0);
//		
//		if(empty($name)) $name = "No Name";
//		
//		if(!empty($id) && !empty($delete)) {
//			if(!empty($delete_move_id)) {
//				$fields = array(
//					DAO_Ticket::TEAM_ID => $delete_move_id
//				);
//				$where = sprintf("%s=%d",
//					DAO_Ticket::TEAM_ID,
//					$id
//				);
//				DAO_Ticket::updateWhere($fields, $where);
//				
//				DAO_Group::deleteTeam($id);
//			}
//			
//		} elseif(!empty($id)) {
//			$fields = array(
//				DAO_Group::TEAM_NAME => $name,
//			);
//			DAO_Group::updateTeam($id, $fields);
//			
//		} else {
//			$fields = array(
//				DAO_Group::TEAM_NAME => $name,
//			);
//			$id = DAO_Group::createTeam($fields);
//		}
//		
//		@$worker_ids = DevblocksPlatform::importGPC($_POST['worker_ids'],'array',array());
//		@$worker_levels = DevblocksPlatform::importGPC($_POST['worker_levels'],'array',array());
//		
//	    @$members = DAO_Group::getTeamMembers($id);
//	    
//	    if(is_array($worker_ids) && !empty($worker_ids))
//	    foreach($worker_ids as $idx => $worker_id) {
//	    	@$level = $worker_levels[$idx];
//	    	if(isset($members[$worker_id]) && empty($level)) {
//	    		DAO_Group::unsetTeamMember($id, $worker_id);
//	    	} elseif(!empty($level)) { // member|manager
//				 DAO_Group::setTeamMember($id, $worker_id, (1==$level)?false:true);
//	    	}
//	    }
//		
//		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','groups')));
//	}
//	
//	
//	function saveIncomingMailSettingsAction() {
//		$translate = DevblocksPlatform::getTranslationService();
//		$worker = PortSensorApplication::getActiveWorker();
//		
//		if(!$worker || !$worker->is_superuser) {
//			echo $translate->_('common.access_denied');
//			return;
//		}
//		
//		if(DEMO_MODE) {
//			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','mail')));
//			return;
//		}
//		
//	    @$attachments_enabled = DevblocksPlatform::importGPC($_POST['attachments_enabled'],'integer',0);
//	    @$attachments_max_size = DevblocksPlatform::importGPC($_POST['attachments_max_size'],'integer',10);
//	    @$parser_autoreq = DevblocksPlatform::importGPC($_POST['parser_autoreq'],'integer',0);
//	    @$parser_autoreq_exclude = DevblocksPlatform::importGPC($_POST['parser_autoreq_exclude'],'string','');
//		
//	    $settings = PortSensorSettings::getInstance();
//	    $settings->set(PortSensorSettings::ATTACHMENTS_ENABLED, $attachments_enabled);
//	    $settings->set(PortSensorSettings::ATTACHMENTS_MAX_SIZE, $attachments_max_size);
//	    $settings->set(PortSensorSettings::PARSER_AUTO_REQ, $parser_autoreq);
//	    $settings->set(PortSensorSettings::PARSER_AUTO_REQ_EXCLUDE, $parser_autoreq_exclude);
//		
//		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','mail')));
//	}
//	
//	// Form Submit
//	function saveOutgoingMailSettingsAction() {
//		$translate = DevblocksPlatform::getTranslationService();
//		$worker = PortSensorApplication::getActiveWorker();
//		
//		if(!$worker || !$worker->is_superuser) {
//			echo $translate->_('common.access_denied');
//			return;
//		}
//		
//		if(DEMO_MODE) {
//			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','mail')));
//			return;
//		}
//		
//	    @$default_reply_address = DevblocksPlatform::importGPC($_REQUEST['sender_address'],'string');
//	    @$default_reply_personal = DevblocksPlatform::importGPC($_REQUEST['sender_personal'],'string');
//	    @$default_signature = DevblocksPlatform::importGPC($_POST['default_signature'],'string');
//	    @$default_signature_pos = DevblocksPlatform::importGPC($_POST['default_signature_pos'],'integer',0);
//	    @$smtp_host = DevblocksPlatform::importGPC($_REQUEST['smtp_host'],'string','localhost');
//	    @$smtp_port = DevblocksPlatform::importGPC($_REQUEST['smtp_port'],'integer',25);
//	    @$smtp_enc = DevblocksPlatform::importGPC($_REQUEST['smtp_enc'],'string','None');
//	    @$smtp_timeout = DevblocksPlatform::importGPC($_REQUEST['smtp_timeout'],'integer',30);
//	    @$smtp_max_sends = DevblocksPlatform::importGPC($_REQUEST['smtp_max_sends'],'integer',20);
//
//	    @$smtp_auth_enabled = DevblocksPlatform::importGPC($_REQUEST['smtp_auth_enabled'],'integer', 0);
//	    if($smtp_auth_enabled) {
//		    @$smtp_auth_user = DevblocksPlatform::importGPC($_REQUEST['smtp_auth_user'],'string');
//		    @$smtp_auth_pass = DevblocksPlatform::importGPC($_REQUEST['smtp_auth_pass'],'string');
//	    	
//	    } else { // need to clear auth info when smtp auth is disabled
//		    @$smtp_auth_user = '';
//		    @$smtp_auth_pass = '';
//	    }
//	    
//	    $settings = PortSensorSettings::getInstance();
//	    $settings->set(PortSensorSettings::DEFAULT_REPLY_FROM, $default_reply_address);
//	    $settings->set(PortSensorSettings::DEFAULT_REPLY_PERSONAL, $default_reply_personal);
//	    $settings->set(PortSensorSettings::DEFAULT_SIGNATURE, $default_signature);
//	    $settings->set(PortSensorSettings::DEFAULT_SIGNATURE_POS, $default_signature_pos);
//	    $settings->set(PortSensorSettings::SMTP_HOST, $smtp_host);
//	    $settings->set(PortSensorSettings::SMTP_PORT, $smtp_port);
//	    $settings->set(PortSensorSettings::SMTP_AUTH_ENABLED, $smtp_auth_enabled);
//	    $settings->set(PortSensorSettings::SMTP_AUTH_USER, $smtp_auth_user);
//	    $settings->set(PortSensorSettings::SMTP_AUTH_PASS, $smtp_auth_pass);
//	    $settings->set(PortSensorSettings::SMTP_ENCRYPTION_TYPE, $smtp_enc);
//	    $settings->set(PortSensorSettings::SMTP_TIMEOUT, !empty($smtp_timeout) ? $smtp_timeout : 30);
//	    $settings->set(PortSensorSettings::SMTP_MAX_SENDS, !empty($smtp_max_sends) ? $smtp_max_sends : 20);
//	    
//	    DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','mail','outgoing','test')));
//	}
//	
//	// Form Submit
//	function saveRoutingAction() {
//		$translate = DevblocksPlatform::getTranslationService();
//		$worker = PortSensorApplication::getActiveWorker();
//		
//		if(!$worker || !$worker->is_superuser) {
//			echo $translate->_('common.access_denied');
//			return;
//		}
//		
//		if(DEMO_MODE) {
//			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','mail')));
//			return;
//		}
//		
//	    @$deletes = DevblocksPlatform::importGPC($_REQUEST['deletes'],'array',array());
//	    @$sticky_ids = DevblocksPlatform::importGPC($_REQUEST['sticky_ids'],'array',array());
//	    @$sticky_order = DevblocksPlatform::importGPC($_REQUEST['sticky_order'],'array',array());
//	    
//	    @$active_worker = PortSensorApplication::getActiveWorker();
//	    if(!$active_worker->is_superuser)
//	    	return;
//	    
//	    // Deletes
//	    if(!empty($deletes)) {
//	    	DAO_MailToGroupRule::delete($deletes);
//	    }
//	    
//	    // Reordering
//	    if(is_array($sticky_ids) && is_array($sticky_order))
//	    foreach($sticky_ids as $idx => $id) {
//	    	@$order = intval($sticky_order[$idx]);
//			DAO_MailToGroupRule::update($id, array (
//	    		DAO_MailToGroupRule::STICKY_ORDER => $order
//	    	));
//	    }
//		
//		// Default group
//	    @$default_group_id = DevblocksPlatform::importGPC($_REQUEST['default_group_id'],'integer','0');
//		DAO_Group::setDefaultGroup($default_group_id);
//		
//		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('setup','parser')));
//	}
//	
//   	function showMailRoutingRulePanelAction() {
//   		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
//   		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);
//
//		$active_worker = PortSensorApplication::getActiveWorker();
//
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl_path = $this->_TPL_PATH;
//		$tpl->assign('path', $tpl_path);
//   		
//		$tpl->assign('group_id', $group_id);
//		
//		if(null != ($rule = DAO_MailToGroupRule::get($id))) {
//			$tpl->assign('rule', $rule);
//		}
//
//		// Make sure we're allowed to change this group's setup
//		if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser) {
//			return;
//		}
//		
//		$groups = DAO_Group::getAll();
//		$tpl->assign('groups', $groups);
//		
//		$workers = DAO_Worker::getAll();
//		$tpl->assign('workers', $workers);
//		
//		// Custom Fields: Address
//		$address_fields = DAO_CustomField::getBySource(PsCustomFieldSource_Address::ID);
//		$tpl->assign('address_fields', $address_fields);
//		
//		// Custom Fields: Orgs
//		$org_fields = DAO_CustomField::getBySource(PsCustomFieldSource_Org::ID);
//		$tpl->assign('org_fields', $org_fields);
//
//		// Custom Fields: Ticket
//		$ticket_fields = DAO_CustomField::getBySource(PsCustomFieldSource_Ticket::ID);
//		$tpl->assign('ticket_fields', $ticket_fields);
//		
//		$tpl->display('file:' . $tpl_path . 'setup/tabs/mail/routing/peek.tpl');
//   	}
//   	
//   	function saveMailRoutingRuleAddAction() {
//   		$translate = DevblocksPlatform::getTranslationService();
//   		
//   		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
//   		
//	    @$active_worker = PortSensorApplication::getActiveWorker();
//	    if(!$active_worker->is_superuser)
//	    	return;
//
//	    /*****************************/
//		@$name = DevblocksPlatform::importGPC($_POST['name'],'string','');
//		@$is_sticky = DevblocksPlatform::importGPC($_POST['is_sticky'],'integer',0);
////		@$is_stackable = DevblocksPlatform::importGPC($_POST['is_stackable'],'integer',0);
//		@$rules = DevblocksPlatform::importGPC($_POST['rules'],'array',array());
//		@$do = DevblocksPlatform::importGPC($_POST['do'],'array',array());
//		
//		if(empty($name))
//			$name = $translate->_('Mail Routing Rule');
//		
//		$criterion = array();
//		$actions = array();
//		
//		// Custom fields
//		$custom_fields = DAO_CustomField::getAll();
//		
//		// Criteria
//		if(is_array($rules))
//		foreach($rules as $rule) {
//			$rule = DevblocksPlatform::strAlphaNumDash($rule);
//			@$value = DevblocksPlatform::importGPC($_POST['value_'.$rule],'string','');
//			
//			// [JAS]: Allow empty $value (null/blank checking)
//			
//			$criteria = array(
//				'value' => $value,
//			);
//			
//			// Any special rule handling
//			switch($rule) {
//				case 'dayofweek':
//					// days
//					$days = DevblocksPlatform::importGPC($_REQUEST['value_dayofweek'],'array',array());
//					if(in_array(0,$days)) $criteria['sun'] = 'Sunday';
//					if(in_array(1,$days)) $criteria['mon'] = 'Monday';
//					if(in_array(2,$days)) $criteria['tue'] = 'Tuesday';
//					if(in_array(3,$days)) $criteria['wed'] = 'Wednesday';
//					if(in_array(4,$days)) $criteria['thu'] = 'Thursday';
//					if(in_array(5,$days)) $criteria['fri'] = 'Friday';
//					if(in_array(6,$days)) $criteria['sat'] = 'Saturday';
//					unset($criteria['value']);
//					break;
//				case 'timeofday':
//					$from = DevblocksPlatform::importGPC($_REQUEST['timeofday_from'],'string','');
//					$to = DevblocksPlatform::importGPC($_REQUEST['timeofday_to'],'string','');
//					$criteria['from'] = $from;
//					$criteria['to'] = $to;
//					unset($criteria['value']);
//					break;
//				case 'subject':
//					break;
//				case 'from':
//					break;
//				case 'tocc':
//					break;
//				case 'header1':
//				case 'header2':
//				case 'header3':
//				case 'header4':
//				case 'header5':
//					if(null != (@$header = DevblocksPlatform::importGPC($_POST[$rule],'string',null)))
//						$criteria['header'] = strtolower($header);
//					break;
//				case 'body':
//					break;
////				case 'attachment':
////					break;
//				default: // ignore invalids // [TODO] Very redundant
//					// Custom fields
//					if("cf_" == substr($rule,0,3)) {
//						$field_id = intval(substr($rule,3));
//						
//						if(!isset($custom_fields[$field_id]))
//							continue;
//
//						switch($custom_fields[$field_id]->type) {
//							case 'S': // string
//							case 'T': // clob
//							case 'U': // URL
//								$oper = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_oper'],'string','regexp');
//								$criteria['oper'] = $oper;
//								break;
//							case 'D': // dropdown
//							case 'M': // multi-dropdown
//							case 'X': // multi-checkbox
//							case 'W': // worker
//								$in_array = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id],'array',array());
//								$out_array = array();
//								
//								// Hash key on the option for quick lookup later
//								if(is_array($in_array))
//								foreach($in_array as $k => $v) {
//									$out_array[$v] = $v;
//								}
//								
//								$criteria['value'] = $out_array;
//								break;
//							case 'E': // date
//								$from = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_from'],'string','0');
//								$to = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_to'],'string','now');
//								$criteria['from'] = $from;
//								$criteria['to'] = $to;
//								unset($criteria['value']);
//								break;
//							case 'N': // number
//								$oper = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_oper'],'string','=');
//								$criteria['oper'] = $oper;
//								$criteria['value'] = intval($value);
//								break;
//							case 'C': // checkbox
//								$criteria['value'] = intval($value);
//								break;
//						}
//						
//					} else {
//						continue;
//					}
//					
//					break;
//			}
//			
//			$criterion[$rule] = $criteria;
//		}
//		
//		// Actions
//		if(is_array($do))
//		foreach($do as $act) {
//			$action = array();
//			
//			switch($act) {
//				// Move group/bucket
//				case 'move':
//					@$move_code = DevblocksPlatform::importGPC($_REQUEST['do_move'],'string',null);
//					if(0 != strlen($move_code)) {
//						list($g_id, $b_id) = PortSensorApplication::translateTeamCategoryCode($move_code);
//						$action = array(
//							'group_id' => intval($g_id),
//							'bucket_id' => intval($b_id),
//						);
//					}
//					break;
//				default: // ignore invalids
//					// Custom fields
//					if("cf_" == substr($act,0,3)) {
//						$field_id = intval(substr($act,3));
//						
//						if(!isset($custom_fields[$field_id]))
//							continue;
//
//						$action = array();
//							
//						switch($custom_fields[$field_id]->type) {
//							case 'S': // string
//							case 'T': // clob
//							case 'U': // URL
//							case 'D': // dropdown
//							case 'W': // worker
//								$value = DevblocksPlatform::importGPC($_REQUEST['do_cf_'.$field_id],'string','');
//								$action['value'] = $value;
//								break;
//							case 'M': // multi-dropdown
//							case 'X': // multi-checkbox
//								$in_array = DevblocksPlatform::importGPC($_REQUEST['do_cf_'.$field_id],'array',array());
//								$out_array = array();
//								
//								// Hash key on the option for quick lookup later
//								if(is_array($in_array))
//								foreach($in_array as $k => $v) {
//									$out_array[$v] = $v;
//								}
//								
//								$action['value'] = $out_array;
//								break;
//							case 'E': // date
//								$value = DevblocksPlatform::importGPC($_REQUEST['do_cf_'.$field_id],'string','');
//								$action['value'] = $value;
//								break;
//							case 'N': // number
//							case 'C': // checkbox
//								$value = DevblocksPlatform::importGPC($_REQUEST['do_cf_'.$field_id],'string','');
//								$action['value'] = intval($value);
//								break;
//						}
//						
//					} else {
//						continue;
//					}
//					break;
//			}
//			
//			$actions[$act] = $action;
//		}
//
//   		$fields = array(
//   			DAO_MailToGroupRule::NAME => $name,
//   			DAO_MailToGroupRule::IS_STICKY => $is_sticky,
//   			DAO_MailToGroupRule::CRITERIA_SER => serialize($criterion),
//   			DAO_MailToGroupRule::ACTIONS_SER => serialize($actions),
//   		);
//
//   		// Only sticky filters can manual order and be stackable
//   		if(!$is_sticky) {
//   			$fields[DAO_MailToGroupRule::STICKY_ORDER] = 0;
////   			$fields[DAO_MailToGroupRule::IS_STACKABLE] = 0;
////   		} else { // is sticky
////   			$fields[DAO_MailToGroupRule::IS_STACKABLE] = $is_stackable;
//   		}
//   		
//   		// Create
//   		if(empty($id)) {
//   			$fields[DAO_MailToGroupRule::POS] = 0;
//	   		$id = DAO_MailToGroupRule::create($fields);
//	   		
//	   	// Update
//   		} else {
//   			DAO_MailToGroupRule::update($id, $fields);
//   		}
//   		
//   		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('setup','parser')));
//   	}	
//	
};

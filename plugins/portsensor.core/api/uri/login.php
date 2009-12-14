<?php
class PsSignInPage extends PortSensorPageExtension {
    const KEY_FORGOT_EMAIL = 'login.recover.email';
    const KEY_FORGOT_SENTCODE = 'login.recover.sentcode';
    const KEY_FORGOT_CODE = 'login.recover.code';
    
	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
		parent::__construct($manifest);
	}
	
	function isVisible() {
		return true;
	}
	
	function render() {
	    $response = DevblocksPlatform::getHttpResponse();
	    $stack = $response->path;
	    array_shift($stack); // login
        $section = array_shift($stack);
        
        switch($section) {
            case "forgot":
                $step = array_shift($stack);
                $tpl = DevblocksPlatform::getTemplateService();
                
                switch($step) {
                    default:
                    case "step1":
                    	if ((@$failed = array_shift($stack)) == "failed") {
                    		$tpl->assign('failed',true);
                    	}
                        $tpl->display('file:' . $this->_TPL_PATH . 'login/forgot1.tpl');
                        break;
                    
                    case "step2":
                        $tpl->display('file:' . $this->_TPL_PATH . 'login/forgot2.tpl');
                        break;
                        
                    case "step3":
                        $tpl->display('file:' . $this->_TPL_PATH . 'login/forgot3.tpl');
                        break;
                }
                break;
                
            default:
				$tpl = DevblocksPlatform::getTemplateService();
				$tpl->display('file:' . $this->_TPL_PATH . 'login/login.tpl');
                break;
        }
	}
	
	// POST
	function authenticateAction() {
		// Pull from $_POST
		@$original_path = explode(',',DevblocksPlatform::importGPC($_POST['original_path']));
		@$email = DevblocksPlatform::importGPC($_POST['email']);
		@$password = DevblocksPlatform::importGPC($_POST['password']);
		
		$url_service = DevblocksPlatform::getUrlService();

		$worker = DAO_Worker::login($email, $password);
		
		if(!is_null($worker)) {
			$session = DevblocksPlatform::getSessionService();
			$visit = new PortSensorVisit();
			$visit->setWorker($worker);
				
			$session->setVisit($visit);
			
			//authentication passed
			if($original_path[0]=='')
				unset($original_path[0]);
			
			$devblocks_response = new DevblocksHttpResponse($original_path);

			// Worker
			$worker = PortSensorApplication::getActiveWorker();

			// Timezone
			if(null != ($timezone = DAO_WorkerPref::get($worker->id,'timezone'))) {
				$_SESSION['timezone'] = $timezone;
				@date_default_timezone_set($timezone);
			}
			
			// Language
			if(null != ($lang_code = DAO_WorkerPref::get($worker->id,'locale'))) {
				$_SESSION['locale'] = $lang_code;
				DevblocksPlatform::setLocale($lang_code);
			}
			
			if(!empty($devblocks_response->path) && $devblocks_response->path[0]=='login') {
//				$tour_enabled = intval(DAO_WorkerPref::get($worker->id, 'assist_mode', 1));
//				$next_page = ($tour_enabled) ?  'welcome' : 'home';
				$next_page = 'home';				
				$devblocks_response = new DevblocksHttpResponse(array($next_page));
			}
			
		} else {
			//authentication failed
			$devblocks_response = new DevblocksHttpResponse(array('login','failed'));
		}
		
		DevblocksPlatform::redirect($devblocks_response);
	}
	
	function signoutAction() {
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(null != ($worker = PortSensorApplication::getActiveWorker())) {
			DAO_Worker::logActivity($worker->id, new Model_Activity(null));
		}
		
		$session->clear();
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')));
	}
	
	// Post
	function doRecoverStep1Action() {
		$translate = DevblocksPlatform::getTranslationService();
		
	    @$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string');
	    
	    $worker = null;
	    $results = DAO_Worker::getWhere(sprintf("%s = %s",DAO_Worker::EMAIL, Ps_ORMHelper::qstr($email)));
	    if(!empty($results))
	    	$worker = array_shift($results);
	    
	    if(empty($email) || empty($worker))
	        return;
	    
	    $_SESSION[self::KEY_FORGOT_EMAIL] = $email;
	    
	    try {
		    $code = PortSensorApplication::generatePassword(10);
		    $_SESSION[self::KEY_FORGOT_SENTCODE] = $code;
		    
	    	$to = $email;
	    	$subject = $translate->_('login.forgot.mail.subject');
	    	$body = vsprintf($translate->_('login.forgot.mail.body'), $code);
	    	
	    	PortSensorMail::quickSend($to, $subject, $body);
	    	
	    } catch (Exception $e) {
	    	DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login','forgot','step1','failed')));
	    }
	    
	    DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login','forgot','step2')));
	}
	
	// Post
	function doRecoverStep2Action() {
        @$code = DevblocksPlatform::importGPC($_REQUEST['code'],'string');

        $email = $_SESSION[self::KEY_FORGOT_EMAIL];
        $sentcode = $_SESSION[self::KEY_FORGOT_SENTCODE];
        $_SESSION[self::KEY_FORGOT_CODE] = $code;
        
	    $worker = null;
	    $results = DAO_Worker::getWhere(sprintf("%s = %s",DAO_Worker::EMAIL,Ps_ORMHelper::qstr($email)));
	    if(!empty($results))
	    	$worker = array_shift($results);
	    
	    if(empty($email) || empty($worker) || empty($code))
	        return;
        
	    if(0 == strcmp($sentcode,$code)) { // passed
            DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('login','forgot','step3')));	        
	    } else {
            DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login','forgot','step2')));
	    }
	}
	
	// Post
	function doRecoverStep3Action() {
        @$password = DevblocksPlatform::importGPC($_REQUEST['password'],'string');

        $email = $_SESSION[self::KEY_FORGOT_EMAIL];
        $sentcode = $_SESSION[self::KEY_FORGOT_SENTCODE];
        $code = $_SESSION[self::KEY_FORGOT_CODE];

	    $worker = null;
	    $results = DAO_Worker::getWhere(sprintf("%s = %s",DAO_Worker::EMAIL,Ps_ORMHelper::qstr($email)));
	    if(!empty($results))
	    	$worker = array_shift($results);
        
	    if(empty($email) || empty($code) || empty($worker))
	        return;
        
	    if(0 == strcmp($sentcode,$code)) { // passed
	        DAO_Worker::update($worker->id, array(
	            DAO_Worker::PASS => md5($password)
	        ));
	        
            unset($_SESSION[self::KEY_FORGOT_EMAIL]);
            unset($_SESSION[self::KEY_FORGOT_CODE]);
            unset($_SESSION[self::KEY_FORGOT_SENTCODE]);
            
            DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')));
	    } else {
	        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login','forgot','step2')));
	    }
        
	}
};

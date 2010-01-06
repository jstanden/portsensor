<?php

class PortSensorVisit extends DevblocksVisit {
	private $worker;

//	const KEY_MY_WORKSPACE = 'view_my_workspace';
	const KEY_HOME_SELECTED_TAB = 'home_selected_tab';

	public function __construct() {
		$this->worker = null;
	}

	/**
	 * @return Model_Worker
	 */
	public function getWorker() {
		return $this->worker;
	}
	
	public function setWorker(Model_Worker $worker=null) {
		$this->worker = $worker;
	}
};

class Model_Activity {
	public $translation_code;
	public $params;

	public function __construct($translation_code='activity.default',$params=array()) {
		$this->translation_code = $translation_code;
		$this->params = $params;
	}

	public function toString(Model_Worker $worker=null) {
		if(null == $worker)
			return;
			
		$translate = DevblocksPlatform::getTranslationService();
		$params = $this->params;

		// Prepend the worker name to the activity's param list
		array_unshift($params, sprintf("<b>%s</b>%s",
			$worker->getName(),
			(!empty($worker->title) 
				? (' (' . $worker->title . ')') 
				: ''
			)
		));
		
		return vsprintf(
			$translate->_($this->translation_code), 
			$params
		);
	}
};

class Model_Alert {
	public $id;
	public $pos;
	public $name;
	public $last_alert_date;
	public $worker_id;
	public $criteria_json;
	public $criteria;
	public $actions_json;
	public $actions;
	public $is_disabled;
	
	/**
	 * @return Model_Sensor[]|false
	 */
	static function getMatches(Model_Sensor $sensor, $only_alert_id=0) {
		$matches = array();
		
		// Check the sensor
		if(null == $sensor)
			return false;

		if(!empty($only_alert_id)) {
			$alerts = array(
				DAO_Alert::get($only_alert_id)
			);
		} else {
			$alerts = DAO_Alert::getWhere(); // [TODO] cache
		}
		
		$custom_fields = DAO_CustomField::getAll();
		
		// Lazy load when needed on criteria basis
		$sensor_field_values = null;
		
		// Criteria extensions
		$alert_criteria_exts = DevblocksPlatform::getExtensions('portsensor.alert.criteria', false);
		
		// Check filters
		if(is_array($alerts))
		foreach($alerts as $alert) { /* @var $alert Model_Alert */
			$passed = 0;

			// Skip alerts with no criteria
			if(!is_array($alert->criteria) || empty($alert->criteria))
				continue; 

			// check criteria
			foreach($alert->criteria as $rule_key => $rule) {
				@$value = $rule['value'];
							
				switch($rule_key) {
					case 'dayofweek':
						$current_day = strftime('%w');
//						$current_day = 1;

						// Forced to English abbrevs as indexes
						$days = array('sun','mon','tue','wed','thu','fri','sat');
						
						// Is the current day enabled?
						if(isset($rule[$days[$current_day]])) {
							$passed++;
						}
							
						break;
						
					case 'timeofday':
						$current_hour = strftime('%H');
						$current_min = strftime('%M');
//						$current_hour = 17;
//						$current_min = 5;

						if(null != ($from_time = @$rule['from']))
							list($from_hour, $from_min) = explode(':', $from_time);
						
						if(null != ($to_time = @$rule['to']))
							if(list($to_hour, $to_min) = explode(':', $to_time));

						// Do we need to wrap around to the next day's hours?
						if($from_hour > $to_hour) { // yes
							$to_hour += 24; // add 24 hrs to the destination (1am = 25th hour)
						}
							
						// Are we in the right 24 hourly range?
						if((integer)$current_hour >= $from_hour && (integer)$current_hour <= $to_hour) {
							// If we're in the first hour, are we minutes early?
							if($current_hour==$from_hour && (integer)$current_min < $from_min)
								break;
							// If we're in the last hour, are we minutes late?
							if($current_hour==$to_hour && (integer)$current_min > $to_min)
								break;
							
							$passed++;
						}
						break;						
						
					case 'event':
						switch($sensor->status) {
							case 0: // OK
								if(isset($rule['ok']))
									$passed++;
								break;
							case 1: // WARNING
								if(isset($rule['warning']))
									$passed++;
								break;
							case 2: // CRITICAL
								if(isset($rule['critical']))
									$passed++;
								break;
							case 3: // M.I.A
								if(isset($rule['mia']))
									$passed++;
								break;
						}
						break;

					case 'sensor_name':
						$regexp_sensor_name = DevblocksPlatform::strToRegExp($value);
						if(@preg_match($regexp_sensor_name, $sensor->name)) {
							$passed++;
						}
						break;
						
					case 'sensor_type':
						if(isset($rule[$sensor->extension_id]))
							$passed++;
						break;

					case 'sensor_fail_count':
						$oper = $rule['oper'];
						
						switch($oper) {
							default:
								if($sensor->fail_count == $value)
									$passed++;
								break;
							case '!=':
								if($sensor->fail_count != $value)
									$passed++;
								break;
							case '>':
								if($sensor->fail_count > $value)
									$passed++;
								break;
							case '<':
								if($sensor->fail_count < $value)
									$passed++;
								break;
						}
						
						if(isset($rule[$sensor->extension_id]))
							$passed++;
						break;
						
					default: // ignore invalids
						// Custom Fields
						if(0==strcasecmp('cf_',substr($rule_key,0,3))) {
							$field_id = substr($rule_key,3);

							// Make sure it exists
							if(null == (@$field = $custom_fields[$field_id]))
								continue;

							// Lazy values loader
							$field_values = array();
							switch($field->source_extension) {
								case PsCustomFieldSource_Sensor::ID:
									if(null == $sensor_field_values)
										$sensor_field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(PsCustomFieldSource_Sensor::ID, $sensor->id));
									$field_values =& $sensor_field_values;
									break;
							}
							
							// No values, default.
//							if(!isset($field_values[$field_id]))
//								continue;
							
							// Type sensitive value comparisons
							// [TODO] Operators
							switch($field->type) {
								case 'S': // string
								case 'T': // clob
								case 'U': // URL
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : '';
									$oper = isset($rule['oper']) ? $rule['oper'] : "=";
									
									if($oper == "=" && @preg_match(DevblocksPlatform::strToRegExp($value, true), $field_val))
										$passed++;
									elseif($oper == "!=" && @!preg_match(DevblocksPlatform::strToRegExp($value, true), $field_val))
										$passed++;
									break;
								case 'N': // number
									if(!isset($field_values[$field_id]))
										break;
								
									$field_val = intval($field_values[$field_id]);
									$oper = isset($rule['oper']) ? $rule['oper'] : "=";
									
									if($oper=="=" && $field_val == intval($value))
										$passed++;
									elseif($oper=="!=" && $field_val != intval($value))
										$passed++;
									elseif($oper==">" && $field_val > intval($value))
										$passed++;
									elseif($oper=="<" && $field_val < intval($value))
										$passed++;
									break;
								case 'E': // date
									$field_val = isset($field_values[$field_id]) ? intval($field_values[$field_id]) : 0;
									$from = isset($rule['from']) ? $rule['from'] : "0";
									$to = isset($rule['to']) ? $rule['to'] : "now";
									
									if(intval(@strtotime($from)) <= $field_val && intval(@strtotime($to)) >= $field_val) {
										$passed++;
									}
									break;
								case 'C': // checkbox
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : 0;
									if(intval($value)==intval($field_val))
										$passed++;
									break;
								case 'D': // dropdown
								case 'X': // multi-checkbox
								case 'M': // multi-picklist
								case 'W': // worker
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : array();
									if(!is_array($value)) $value = array($value);
										
									if(is_array($field_val)) { // if multiple things set
										foreach($field_val as $v) { // loop through possible
											if(isset($value[$v])) { // is any possible set?
												$passed++;
												break;
											}
										}
										
									} else { // single
										if(isset($value[$field_val])) { // is our set field in possibles?
											$passed++;
											break;
										}
										
									}
									break;
							}
						} elseif(isset($alert_criteria_exts[$rule_key])) { // criteria extensions
							try {
								$crit_ext = $alert_criteria_exts[$rule_key]->createInstance();
								if($crit_ext->matches($alert, $sensor)) {
									$passed++;
									break;
								}
								
							} catch(Exception $e) {
								// Oops!
								//print_r($e);
							}
							
						}
						
						break;
				}
			}
			
			// If our rule matched every criteria, stop and return the alert
			if($passed == count($alert->criteria)) {
//				DAO_Alert::increment($alert->id); // ++ the times we've matched
				$matches[$alert->id] = $alert;
				
				// If we're not stackable anymore, bail out.
//				if(!$alert->is_stackable)
//					return $matches;
			}
		}
		
		// If last alert was still stackable...
		if(!empty($matches))
			return $matches;
		
		// No matches
		return false;
	}
	
	/**
	 * @param integer[] $sensor_ids
	 */
	function run(Model_Sensor $sensor) {
		$fields = array();
		$field_values = array();

		// Custom fields
		$custom_fields = DAO_CustomField::getAll();
		
		// Action extensions
		$alert_action_exts = DevblocksPlatform::getExtensions('portsensor.alert.action', false);
		
		// actions
		if(is_array($this->actions))
		foreach($this->actions as $action => $params) {
			switch($action) {
//				case 'spam':
//					if(isset($params['is_spam'])) {
//						if(intval($params['is_spam'])) {
//							foreach($ticket_ids as $ticket_id)
//								CerberusBayes::markTicketAsSpam($ticket_id);
//						} else {
//							foreach($ticket_ids as $ticket_id)
//								CerberusBayes::markTicketAsNotSpam($ticket_id);
//						}
//					}
//					break;

				default:
					// Custom fields
					if(substr($action,0,3)=="cf_") {
						$field_id = intval(substr($action,3));
						
						if(!isset($custom_fields[$field_id]) || !isset($params['value']))
							break;

						$field_values[$field_id] = $params;
						
					} elseif(isset($alert_action_exts[$action])) { // criteria extensions
						try {
							$act_ext = $alert_action_exts[$action]->createInstance();
							$act_ext->run($this, $sensor);
							
						} catch(Exception $e) {
							// Oops!
							//print_r($e);
						}
					}
					break;
			}
		}

		if(!empty($sensor)) {
			if(!empty($fields))
				DAO_Sensor::update($sensor->id, $fields);
			
			// Custom Fields
			Ps_AbstractView::_doBulkSetCustomFields(PsCustomFieldSource_Sensor::ID, $field_values, array($sensor->id));
		}
	}	
};

class Model_CustomField {
	const TYPE_CHECKBOX = 'C';
	const TYPE_DROPDOWN = 'D';
	const TYPE_DATE = 'E';
	const TYPE_MULTI_PICKLIST = 'M';
	const TYPE_NUMBER = 'N';
	const TYPE_SINGLE_LINE = 'S';
	const TYPE_MULTI_LINE = 'T';
	const TYPE_URL = 'U';
	const TYPE_WORKER = 'W';
	const TYPE_MULTI_CHECKBOX = 'X';
	
	public $id = 0;
	public $name = '';
	public $type = '';
	public $group_id = 0;
	public $source_extension = '';
	public $pos = 0;
	public $options = array();
	
	static function getTypes() {
		return array(
			self::TYPE_SINGLE_LINE => 'Text: Single Line',
			self::TYPE_MULTI_LINE => 'Text: Multi-Line',
			self::TYPE_NUMBER => 'Number',
			self::TYPE_DATE => 'Date',
			self::TYPE_DROPDOWN => 'Picklist',
			self::TYPE_MULTI_PICKLIST => 'Multi-Picklist',
			self::TYPE_CHECKBOX => 'Checkbox',
			self::TYPE_MULTI_CHECKBOX => 'Multi-Checkbox',
			self::TYPE_WORKER => 'Worker',
			self::TYPE_URL => 'URL',
//			self::TYPE_FILE => 'File',
		);
	}
};

class Model_Sensor {
	public $id = 0;
	public $name = '';
	public $extension_id = '';
	public $params = array();
	public $updated_date = 0;
	public $status = 0;
	public $metric = '';
	public $output = '';
	public $is_disabled = 0;
	public $fail_count = 0;
	
	public function getStatusName() {
		$translate = DevblocksPlatform::getTranslationService();
		
		if($this->is_disabled)
			return $translate->_('sensor.status.disabled');
		
		switch($this->status) {
			default:
			case 0:
				return $translate->_('sensor.status.ok');
				break;
			case 1:
				return $translate->_('sensor.status.warning');
				break;
			case 2:
				return $translate->_('sensor.status.critical');
				break;
			case 3:
				return $translate->_('sensor.status.mia');
				break;
		}
	}
};

abstract class Ps_AbstractView {
	public $id = 0;
	public $name = "";
	public $view_columns = array();
	public $params = array();

	public $renderPage = 0;
	public $renderLimit = 10;
	public $renderSortBy = '';
	public $renderSortAsc = 1;

	function getData() {
	}

	function render() {
		echo ' '; // Expect Override
	}

	function renderCriteria($field) {
		echo ' '; // Expect Override
	}

	protected function _renderCriteriaCustomField($tpl, $field_id) {
		$field = DAO_CustomField::get($field_id);
		$tpl_path = DEVBLOCKS_PLUGIN_PATH . 'portsensor.core/templates/';
		
		switch($field->type) {
			case Model_CustomField::TYPE_DROPDOWN:
			case Model_CustomField::TYPE_MULTI_PICKLIST:
			case Model_CustomField::TYPE_MULTI_CHECKBOX:
				$tpl->assign('field', $field);
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__cfield_picklist.tpl');
				break;
			case Model_CustomField::TYPE_CHECKBOX:
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__cfield_checkbox.tpl');
				break;
			case Model_CustomField::TYPE_DATE:
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__date.tpl');
				break;
			case Model_CustomField::TYPE_NUMBER:
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__number.tpl');
				break;
			case Model_CustomField::TYPE_WORKER:
				$tpl->assign('workers', DAO_Worker::getAllActive());
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__worker.tpl');
				break;
			default:
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__string.tpl');
				break;
		}
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $field
	 * @param string $oper
	 * @param string $value
	 * @abstract
	 */
	function doSetCriteria($field, $oper, $value) {
		// Expect Override
	}

	protected function _doSetCriteriaCustomField($token, $field_id) {
		$field = DAO_CustomField::get($field_id);
		@$oper = DevblocksPlatform::importGPC($_POST['oper'],'string','');
		@$value = DevblocksPlatform::importGPC($_POST['value'],'string','');
		
		$criteria = null;
		
		switch($field->type) {
			case Model_CustomField::TYPE_DROPDOWN:
			case Model_CustomField::TYPE_MULTI_PICKLIST:
			case Model_CustomField::TYPE_MULTI_CHECKBOX:
				@$options = DevblocksPlatform::importGPC($_POST['options'],'array',array());
				if(!empty($options)) {
					$criteria = new DevblocksSearchCriteria($token,$oper,$options);
				} else {
					$criteria = new DevblocksSearchCriteria($token,DevblocksSearchCriteria::OPER_IS_NULL);
				}
				break;
			case Model_CustomField::TYPE_CHECKBOX:
				$criteria = new DevblocksSearchCriteria($token,$oper,!empty($value) ? 1 : 0);
				break;
			case Model_CustomField::TYPE_NUMBER:
				$criteria = new DevblocksSearchCriteria($token,$oper,intval($value));
				break;
			case Model_CustomField::TYPE_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');
	
				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';
	
				$criteria = new DevblocksSearchCriteria($token,$oper,array($from,$to));
				break;
			case Model_CustomField::TYPE_WORKER:
				@$oper = DevblocksPlatform::importGPC($_REQUEST['oper'],'string','eq');
				@$worker_ids = DevblocksPlatform::importGPC($_POST['worker_id'],'array',array());
				
				$criteria = new DevblocksSearchCriteria($token,$oper,$worker_ids);
				break;
			default: // TYPE_SINGLE_LINE || TYPE_MULTI_LINE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($token,$oper,$value);
				break;
		}
		
		return $criteria;
	}
	
	/**
	 * This method automatically fixes any cached strange options, like 
	 * deleted custom fields.
	 *
	 */
	protected function _sanitize() {
		$fields = $this->getColumns();
		$custom_fields = DAO_CustomField::getAll();
		$needs_save = false;
		
		// Parameter sanity check
		if(is_array($this->params))
		foreach($this->params as $pidx => $null) {
			if(substr($pidx,0,3)!="cf_")
				continue;
				
			if(0 != ($cf_id = intval(substr($pidx,3)))) {
				// Make sure our custom fields still exist
				if(!isset($custom_fields[$cf_id])) {
					unset($this->params[$pidx]);
					$needs_save = true;
				}
			}
		}
		
		// View column sanity check
		if(is_array($this->view_columns))
		foreach($this->view_columns as $cidx => $c) {
			// Custom fields
			if(substr($c,0,3) == "cf_") {
				if(0 != ($cf_id = intval(substr($c,3)))) {
					// Make sure our custom fields still exist
					if(!isset($custom_fields[$cf_id])) {
						unset($this->view_columns[$cidx]);
						$needs_save = true;
					}
				}
			} else {
				// If the column no longer exists (rare but worth checking)
				if(!isset($fields[$c])) {
					unset($this->view_columns[$cidx]);
					$needs_save = true;
				}
			}
		}
		
		// Sort by sanity check
		if(substr($this->renderSortBy,0,3)=="cf_") {
			if(0 != ($cf_id = intval(substr($this->renderSortBy,3)))) {
				if(!isset($custom_fields[$cf_id])) {
					$this->renderSortBy = null;
					$needs_save = true;
				}
			}
    	}
    	
    	if($needs_save) {
    		Ps_AbstractViewLoader::setView($this->id, $this);
    	}
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		$vals = $param->value;

		if(!is_array($vals))
			$vals = array($vals);

		// Do we need to do anything special on custom fields?
		if('cf_'==substr($field,0,3)) {
			$field_id = intval(substr($field,3));
			$custom_fields = DAO_CustomField::getAll();
			
			switch($custom_fields[$field_id]->type) {
				case Model_CustomField::TYPE_WORKER:
					$workers = DAO_Worker::getAll();
					foreach($vals as $idx => $worker_id) {
						if(isset($workers[$worker_id]))
							$vals[$idx] = $workers[$worker_id]->getName(); 
					}
					break;
			}
		}
		
		echo implode(', ', $vals);
	}

	/**
	 * All the view's available fields
	 *
	 * @return array
	 */
	static function getFields() {
		// Expect Override
		return array();
	}

	/**
	 * All searchable fields
	 *
	 * @return array
	 */
	static function getSearchFields() {
		// Expect Override
		return array();
	}

	/**
	 * All fields that can be displayed as columns in the view
	 *
	 * @return array
	 */
	static function getColumns() {
		// Expect Override
		return array();
	}

	function doCustomize($columns, $num_rows=10) {
		$this->renderLimit = $num_rows;

		$viewColumns = array();
		foreach($columns as $col) {
			if(empty($col))
			continue;
			$viewColumns[] = $col;
		}

		$this->view_columns = $viewColumns;
	}

	function doSortBy($sortBy) {
		$iSortAsc = intval($this->renderSortAsc);

		// [JAS]: If clicking the same header, toggle asc/desc.
		if(0 == strcasecmp($sortBy,$this->renderSortBy)) {
			$iSortAsc = (0 == $iSortAsc) ? 1 : 0;
		} else { // [JAS]: If a new header, start with asc.
			$iSortAsc = 1;
		}

		$this->renderSortBy = $sortBy;
		$this->renderSortAsc = $iSortAsc;
	}

	function doPage($page) {
		$this->renderPage = $page;
	}

	function doRemoveCriteria($field) {
		unset($this->params[$field]);
		$this->renderPage = 0;
	}

	function doResetCriteria() {
		$this->params = array();
		$this->renderPage = 0;
	}
	
	public static function _doBulkSetCustomFields($source_extension,$custom_fields, $ids) {
		$fields = DAO_CustomField::getAll();
		
		if(!empty($custom_fields))
		foreach($custom_fields as $cf_id => $params) {
			if(!is_array($params) || !isset($params['value']))
				continue;
				
			$cf_val = $params['value'];
			
			// Data massaging
			switch($fields[$cf_id]->type) {
				case Model_CustomField::TYPE_DATE:
					$cf_val = intval(@strtotime($cf_val));
					break;
				case Model_CustomField::TYPE_CHECKBOX:
				case Model_CustomField::TYPE_NUMBER:
					$cf_val = (0==strlen($cf_val)) ? '' : intval($cf_val);
					break;
			}

			// If multi-selection types, handle delta changes
			if(Model_CustomField::TYPE_MULTI_PICKLIST==$fields[$cf_id]->type 
				|| Model_CustomField::TYPE_MULTI_CHECKBOX==$fields[$cf_id]->type) {
				if(is_array($cf_val))
				foreach($cf_val as $val) {
					$op = substr($val,0,1);
					$val = substr($val,1);
				
					if(is_array($ids))
					foreach($ids as $id) {
						if($op=='+')
							DAO_CustomFieldValue::setFieldValue($source_extension,$id,$cf_id,$val,true);
						elseif($op=='-')
							DAO_CustomFieldValue::unsetFieldValue($source_extension,$id,$cf_id,$val);
					}
				}
					
			// Otherwise, set/unset as a single field
			} else {
				if(is_array($ids))
				foreach($ids as $id) {
					if(0 != strlen($cf_val))
						DAO_CustomFieldValue::setFieldValue($source_extension,$id,$cf_id,$cf_val);
					else
						DAO_CustomFieldValue::unsetFieldValue($source_extension,$id,$cf_id);
				}
			}
		}
	}
};

/**
 * Used to persist a Ps_AbstractView instance and not be encumbered by
 * classloading issues (out of the session) from plugins that might have
 * concrete AbstractView implementations.
 */
class Ps_AbstractViewModel {
	public $class_name = '';

	public $id = 0;
	public $name = "";
	public $view_columns = array();
	public $params = array();

	public $renderPage = 0;
	public $renderLimit = 10;
	public $renderSortBy = '';
	public $renderSortAsc = 1;
};

/**
 * This is essentially an AbstractView Factory
 */
class Ps_AbstractViewLoader {
	static $views = null;
	const VISIT_ABSTRACTVIEWS = 'abstractviews_list';

	static private function _init() {
		$visit = PortSensorApplication::getVisit();
		self::$views = $visit->get(self::VISIT_ABSTRACTVIEWS,array());
	}

	/**
	 * @param string $view_label Abstract view identifier
	 * @return boolean
	 */
	static function exists($view_label) {
		if(is_null(self::$views)) self::_init();
		return isset(self::$views[$view_label]);
	}

	/**
	 * Enter description here...
	 *
	 * @param string $class Ps_AbstractView
	 * @param string $view_label ID
	 * @return Ps_AbstractView instance
	 */
	static function getView($view_label, Ps_AbstractViewModel $defaults=null) {
		$active_worker = PortSensorApplication::getActiveWorker();
		if(is_null(self::$views)) self::_init();

		if(self::exists($view_label)) {
			$model = self::$views[$view_label];
			return self::unserializeAbstractView($model);
			
		} else {
			// See if the worker has their own saved prefs
			@$prefs = unserialize(DAO_WorkerPref::get($active_worker->id, 'view'.$view_label));

			// If no worker prefsd, check if we're passed defaults
			if((empty($prefs) || !$prefs instanceof Ps_AbstractViewModel) && !empty($defaults))
				$prefs = $defaults;
			
			// Create a default view if it doesn't exist
			if(!empty($prefs) && $prefs instanceof Ps_AbstractViewModel) {
				if(!empty($prefs->class_name) || class_exists($prefs->class_name)) {
					$view = new $prefs->class_name;
					$view->id = $view_label;
					if(!empty($prefs->view_columns))
						$view->view_columns = $prefs->view_columns;
					if(!empty($prefs->renderLimit))
						$view->renderLimit = $prefs->renderLimit;
					if(null !== $prefs->renderSortBy)
						$view->renderSortBy = $prefs->renderSortBy;
					if(null !== $prefs->renderSortAsc)
						$view->renderSortAsc = $prefs->renderSortAsc;
					self::setView($view_label, $view);
					return $view;
				}
			}
			
		}

		return null;
	}

	/**
	 * Enter description here...
	 *
	 * @param string $class Ps_AbstractView
	 * @param string $view_label ID
	 * @param Ps_AbstractView $view
	 */
	static function setView($view_label, $view) {
		if(is_null(self::$views)) self::_init();
		self::$views[$view_label] = self::serializeAbstractView($view);
		self::_save();
	}

	static function deleteView($view_label) {
		unset(self::$views[$view_label]);
		self::_save();
	}
	
	static private function _save() {
		// persist
		$visit = PortSensorApplication::getVisit();
		$visit->set(self::VISIT_ABSTRACTVIEWS, self::$views);
	}

	static function serializeAbstractView($view) {
		if(!$view instanceof Ps_AbstractView) {
			return null;
		}

		$model = new Ps_AbstractViewModel();
			
		$model->class_name = get_class($view);

		$model->id = $view->id;
		$model->name = $view->name;
		$model->view_columns = $view->view_columns;
		$model->params = $view->params;

		$model->renderPage = $view->renderPage;
		$model->renderLimit = $view->renderLimit;
		$model->renderSortBy = $view->renderSortBy;
		$model->renderSortAsc = $view->renderSortAsc;

		return $model;
	}

	static function unserializeAbstractView(Ps_AbstractViewModel $model) {
		if(!class_exists($model->class_name, true))
			return null;
		
		if(null == ($inst = new $model->class_name))
			return null;

		/* @var $inst Ps_AbstractView */
			
		$inst->id = $model->id;
		$inst->name = $model->name;
		$inst->view_columns = $model->view_columns;
		$inst->params = $model->params;

		$inst->renderPage = $model->renderPage;
		$inst->renderLimit = $model->renderLimit;
		$inst->renderSortBy = $model->renderSortBy;
		$inst->renderSortAsc = $model->renderSortAsc;

		return $inst;
	}
};

class Model_Worker {
	public $id;
	public $first_name;
	public $last_name;
	public $title;
	public $email;
	public $pass;
	public $is_superuser;
	public $last_activity_date;
	public $last_activity;
	public $is_disabled;
	
	function hasPriv($priv_id) {
		// We don't need to do much work if we're a superuser
		if($this->is_superuser)
			return true;
		
		$settings = PortSensorSettings::getInstance();
		$acl_enabled = $settings->get(PortSensorSettings::ACL_ENABLED);
			
		// ACL is a paid feature (please respect the licensing and support the project!)
		$license = PortSensorLicense::getInstance();
		if(!$acl_enabled || !isset($license['serial']) || isset($license['a']))
			return ("core.setup"==substr($priv_id,0,11)) ? false : true;
			
		// Check the aggregated worker privs from roles
		$acl = DAO_WorkerRole::getACL();
		$privs_by_worker = $acl[DAO_WorkerRole::CACHE_KEY_PRIVS_BY_WORKER];
		
		if(!empty($priv_id) && isset($privs_by_worker[$this->id][$priv_id]))
			return true;
			
		return false;
	}
	
	function getName($reverse=false) {
		if(!$reverse) {
			$name = sprintf("%s%s%s",
				$this->first_name,
				(!empty($this->first_name) && !empty($this->last_name)) ? " " : "",
				$this->last_name
			);
		} else {
			$name = sprintf("%s%s%s",
				$this->last_name,
				(!empty($this->first_name) && !empty($this->last_name)) ? ", " : "",
				$this->first_name
			);
		}
		
		return $name;
	}
};

class Model_WorkerRole {
	public $id;
	public $name;
};

class Model_Worklist {
	public $id;
	public $worker_id;
	public $workspace;
	public $view_json;
	public $view_pos;
	public $source_extension;
};

class Model_WorklistView {
	public $title = 'New List';
//	public $workspace = '';
	public $columns = array();
	public $num_rows = 10;
	public $params = array();
	public $sort_by = null;
	public $sort_asc = 1;
};

class Ps_AlertView extends Ps_AbstractView {
	const DEFAULT_ID = 'alerts';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Alerts';
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Alert::POS;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_Alert::NAME,
			SearchFields_Alert::POS,
			SearchFields_Alert::LAST_ALERT_DATE,
		);
		
		$this->doResetCriteria();
	}

	function getData() {
		return DAO_Alert::search(
			$this->view_columns,
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

//		$custom_fields = DAO_CustomField::getBySource(PsCustomFieldSource_Sensor::ID);
//		$tpl->assign('custom_fields', $custom_fields);

//		$sensor_types = DevblocksPlatform::getExtensions('portsensor.sensor',false);
//		$tpl->assign('sensor_types', $sensor_types);
		
		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'portsensor.core/templates/alerts/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Alert::NAME:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'portsensor.core/templates/internal/views/criteria/__string.tpl');
				break;
			case SearchFields_Alert::POS:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'portsensor.core/templates/internal/views/criteria/__number.tpl');
				break;
			case SearchFields_Alert::IS_DISABLED:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'portsensor.core/templates/internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_Alert::LAST_ALERT_DATE:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'portsensor.core/templates/internal/views/criteria/__date.tpl');
				break;
			case SearchFields_Alert::WORKER_ID:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'portsensor.core/templates/internal/views/criteria/__worker.tpl');
				break;
			default:
				// Custom Fields
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_Alert::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$strings = array();

				foreach($values as $val) {
					if(empty($val))
					$strings[] = "Nobody";
					elseif(!isset($workers[$val]))
					continue;
					else
					$strings[] = $workers[$val]->getName();
				}
				echo implode(", ", $strings);
				break;
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	static function getFields() {
		return SearchFields_Alert::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_Alert::ID]);
		unset($fields[SearchFields_Alert::CRITERIA_JSON]);
		unset($fields[SearchFields_Alert::ACTIONS_JSON]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		unset($fields[SearchFields_Alert::CRITERIA_JSON]);
		unset($fields[SearchFields_Alert::ACTIONS_JSON]);
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
		$this->params = array(
			SearchFields_Alert::IS_DISABLED => new DevblocksSearchCriteria(SearchFields_Alert::IS_DISABLED,'=',0),
		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Alert::NAME:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_Alert::LAST_ALERT_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;

			case SearchFields_Alert::POS:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			case SearchFields_Alert::WORKER_ID:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
				
			case SearchFields_Alert::IS_DISABLED:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}

	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // [TODO] Temp!
	  
		$change_fields = array();
		$custom_fields = array();

		if(empty($do))
			return;

		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
//				case 'is_disabled':
//					$change_fields[DAO_Sensor::IS_DISABLED] = intval($v);
//					break;
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;

			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_Alert::search(
			array(),
			$this->params,
			100,
			$pg++,
			SearchFields_Alert::ID,
			true,
			false
			);
			 
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			DAO_Alert::update($batch_ids, $change_fields);
			
			// Custom Fields
//			self::_doBulkSetCustomFields(PsCustomFieldSource_Sensor::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}

};

class Ps_SensorView extends Ps_AbstractView {
	const DEFAULT_ID = 'sensors';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Sensors';
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Sensor::UPDATED_DATE;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Sensor::NAME,
//			SearchFields_Sensor::EXTENSION_ID,
			SearchFields_Sensor::STATUS,
			SearchFields_Sensor::UPDATED_DATE,
//			SearchFields_Sensor::FAIL_COUNT,
			SearchFields_Sensor::OUTPUT,
		);
		
		$this->doResetCriteria();
	}

	function getData() {
		return DAO_Sensor::search(
			$this->view_columns,
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$custom_fields = DAO_CustomField::getBySource(PsCustomFieldSource_Sensor::ID);
		$tpl->assign('custom_fields', $custom_fields);

		$sensor_types = DevblocksPlatform::getExtensions('portsensor.sensor',false);
		$tpl->assign('sensor_types', $sensor_types);
		
		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'portsensor.core/templates/sensors/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Sensor::METRIC:
			case SearchFields_Sensor::NAME:
			case SearchFields_Sensor::OUTPUT:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'portsensor.core/templates/internal/views/criteria/__string.tpl');
				break;
			case SearchFields_Sensor::FAIL_COUNT:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'portsensor.core/templates/internal/views/criteria/__number.tpl');
				break;
			case SearchFields_Sensor::IS_DISABLED:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'portsensor.core/templates/internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_Sensor::UPDATED_DATE:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'portsensor.core/templates/internal/views/criteria/__date.tpl');
				break;
			case SearchFields_Sensor::STATUS:
				// [TODO] Translate statuses
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'portsensor.core/templates/sensors/criteria/status.tpl');
				break;
			case SearchFields_Sensor::EXTENSION_ID:
				$sensor_type_mfts = DevblocksPlatform::getExtensions('portsensor.sensor', false);
				$tpl->assign('sensor_type_mfts', $sensor_type_mfts);
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'portsensor.core/templates/sensors/criteria/extension_id.tpl');
				break;
			default:
				// Custom Fields
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		$translate = DevblocksPlatform::getTranslationService();
		
		switch($field) {
			case SearchFields_Sensor::STATUS:
				$strings = array();
				
				if(is_array($values))
				foreach($values as $val) {
					switch($val) {
						case 0:
							$strings[] = $translate->_('sensor.status.ok');
							break;
						case 1:
							$strings[] = $translate->_('sensor.status.warning');
							break;
						case 2:
							$strings[] = $translate->_('sensor.status.critical');
							break;
						case 3:
							$strings[] = $translate->_('sensor.status.mia');
							break;
					}
				}
				echo implode(", ", $strings);
				break;
				
			case SearchFields_Sensor::EXTENSION_ID:
				$sensor_type_mfts = DevblocksPlatform::getExtensions('portsensor.sensor', false);
				$strings = array();

				if(is_array($values))
				foreach($values as $val) {
					if(empty($val))
						$strings[] = "";
					elseif(!isset($sensor_type_mfts[$val]))
						continue;
					else
						$strings[] = $sensor_type_mfts[$val]->name;
				}
				echo implode(", ", $strings);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	static function getFields() {
		return SearchFields_Sensor::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_Sensor::ID]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
		$this->params = array(
			SearchFields_Sensor::IS_DISABLED => new DevblocksSearchCriteria(SearchFields_Sensor::IS_DISABLED,'=',0),
		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Sensor::METRIC:
			case SearchFields_Sensor::NAME:
			case SearchFields_Sensor::OUTPUT:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_Sensor::UPDATED_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;

			case SearchFields_Sensor::FAIL_COUNT:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Sensor::IS_DISABLED:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;

			case SearchFields_Sensor::STATUS:
				@$statuses = DevblocksPlatform::importGPC($_REQUEST['statuses'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$statuses);
				break;
			
			case SearchFields_Sensor::EXTENSION_ID:
				@$sensor_types = DevblocksPlatform::importGPC($_REQUEST['sensor_types'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$sensor_types);
				break;
				
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}

	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // [TODO] Temp!
	  
		$change_fields = array();
		$custom_fields = array();

		if(empty($do))
			return;

		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
//				case 'is_disabled':
//					$change_fields[DAO_Sensor::IS_DISABLED] = intval($v);
//					break;
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;

			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_Sensor::search(
			array(),
			$this->params,
			100,
			$pg++,
			SearchFields_Sensor::ID,
			true,
			false
			);
			 
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			DAO_Sensor::update($batch_ids, $change_fields);
			
			// Custom Fields
			self::_doBulkSetCustomFields(PsCustomFieldSource_Sensor::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}

};

class Ps_WorkerView extends Ps_AbstractView {
	const DEFAULT_ID = 'workers';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Workers';
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Worker::FIRST_NAME;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Worker::FIRST_NAME,
			SearchFields_Worker::LAST_NAME,
			SearchFields_Worker::TITLE,
			SearchFields_Worker::EMAIL,
			SearchFields_Worker::LAST_ACTIVITY_DATE,
			SearchFields_Worker::IS_SUPERUSER,
		);
		
		$this->doResetCriteria();
	}

	function getData() {
		return DAO_Worker::search(
			$this->view_columns,
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$custom_fields = DAO_CustomField::getBySource(PsCustomFieldSource_Worker::ID);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'portsensor.core/templates/setup/tabs/workers/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Worker::EMAIL:
			case SearchFields_Worker::FIRST_NAME:
			case SearchFields_Worker::LAST_NAME:
			case SearchFields_Worker::TITLE:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'portsensor.core/templates/internal/views/criteria/__string.tpl');
				break;
			case SearchFields_Worker::IS_DISABLED:
			case SearchFields_Worker::IS_SUPERUSER:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'portsensor.core/templates/internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_Worker::LAST_ACTIVITY_DATE:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'portsensor.core/templates/internal/views/criteria/__date.tpl');
				break;
			default:
				// Custom Fields
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
//			case SearchFields_WorkerEvent::WORKER_ID:
//				$workers = DAO_Worker::getAll();
//				$strings = array();
//
//				foreach($values as $val) {
//					if(empty($val))
//					$strings[] = "Nobody";
//					elseif(!isset($workers[$val]))
//					continue;
//					else
//					$strings[] = $workers[$val]->getName();
//				}
//				echo implode(", ", $strings);
//				break;
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	static function getFields() {
		return SearchFields_Worker::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_Worker::ID]);
		unset($fields[SearchFields_Worker::LAST_ACTIVITY]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		unset($fields[SearchFields_Worker::LAST_ACTIVITY]);
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
//		$this->params = array(
//			SearchFields_WorkerEvent::NUM_NONSPAM => new DevblocksSearchCriteria(SearchFields_WorkerEvent::NUM_NONSPAM,'>',0),
//		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Worker::EMAIL:
			case SearchFields_Worker::FIRST_NAME:
			case SearchFields_Worker::LAST_NAME:
			case SearchFields_Worker::TITLE:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_Worker::LAST_ACTIVITY_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case SearchFields_Worker::IS_DISABLED:
			case SearchFields_Worker::IS_SUPERUSER:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}

	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // [TODO] Temp!
	  
		$change_fields = array();
		$custom_fields = array();

		if(empty($do))
			return;

		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				case 'is_disabled':
					$change_fields[DAO_Worker::IS_DISABLED] = intval($v);
					break;
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;

			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_Worker::search(
			array(),
			$this->params,
			100,
			$pg++,
			SearchFields_Worker::ID,
			true,
			false
			);
			 
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			DAO_Worker::update($batch_ids, $change_fields);
			
			// Custom Fields
			self::_doBulkSetCustomFields(PsCustomFieldSource_Worker::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}

};

class Model_WorkerEvent {
	public $id;
	public $created_date;
	public $worker_id;
	public $title;
	public $content;
	public $is_read;
	public $url;
};

class Ps_WorkerEventView extends Ps_AbstractView {
	const DEFAULT_ID = 'worker_events';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Worker Events';
		$this->renderLimit = 100;
		$this->renderSortBy = SearchFields_WorkerEvent::CREATED_DATE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_WorkerEvent::CONTENT,
			SearchFields_WorkerEvent::CREATED_DATE,
		);
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_WorkerEvent::search(
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
		return $objects;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'portsensor.core/templates/home/tabs/my_notifications/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_WorkerEvent::TITLE:
			case SearchFields_WorkerEvent::CONTENT:
			case SearchFields_WorkerEvent::URL:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'portsensor.core/templates/internal/views/criteria/__string.tpl');
				break;
//			case SearchFields_WorkerEvent::ID:
//			case SearchFields_WorkerEvent::MESSAGE_ID:
//			case SearchFields_WorkerEvent::TICKET_ID:
//			case SearchFields_WorkerEvent::FILE_SIZE:
//				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'portsensor.core/templates/internal/views/criteria/__number.tpl');
//				break;
			case SearchFields_WorkerEvent::IS_READ:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'portsensor.core/templates/internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_WorkerEvent::CREATED_DATE:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'portsensor.core/templates/internal/views/criteria/__date.tpl');
				break;
			case SearchFields_WorkerEvent::WORKER_ID:
				$workers = DAO_Worker::getAllActive();
				$tpl->assign('workers', $workers);
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'portsensor.core/templates/internal/views/criteria/__worker.tpl');
				break;
			default:
				echo '';
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_WorkerEvent::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$strings = array();

				foreach($values as $val) {
					if(empty($val))
					$strings[] = "Nobody";
					elseif(!isset($workers[$val]))
					continue;
					else
					$strings[] = $workers[$val]->getName();
				}
				echo implode(", ", $strings);
				break;
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	static function getFields() {
		return SearchFields_WorkerEvent::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_WorkerEvent::ID]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
//		$this->params = array(
//			SearchFields_WorkerEvent::NUM_NONSPAM => new DevblocksSearchCriteria(SearchFields_WorkerEvent::NUM_NONSPAM,'>',0),
//		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_WorkerEvent::TITLE:
			case SearchFields_WorkerEvent::CONTENT:
			case SearchFields_WorkerEvent::URL:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_WorkerEvent::WORKER_ID:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
				
			case SearchFields_WorkerEvent::CREATED_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case SearchFields_WorkerEvent::IS_READ:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}

//	function doBulkUpdate($filter, $do, $ids=array()) {
//		@set_time_limit(600); // [TODO] Temp!
//	  
//		$change_fields = array();
//
//		if(empty($do))
//		return;
//
//		if(is_array($do))
//		foreach($do as $k => $v) {
//			switch($k) {
//				case 'banned':
//					$change_fields[DAO_Address::IS_BANNED] = intval($v);
//					break;
//			}
//		}
//
//		$pg = 0;
//
//		if(empty($ids))
//		do {
//			list($objects,$null) = DAO_Address::search(
//			$this->params,
//			100,
//			$pg++,
//			SearchFields_Address::ID,
//			true,
//			false
//			);
//			 
//			$ids = array_merge($ids, array_keys($objects));
//			 
//		} while(!empty($objects));
//
//		$batch_total = count($ids);
//		for($x=0;$x<=$batch_total;$x+=100) {
//			$batch_ids = array_slice($ids,$x,100);
//			DAO_Address::update($batch_ids, $change_fields);
//			unset($batch_ids);
//		}
//
//		unset($ids);
//	}

};
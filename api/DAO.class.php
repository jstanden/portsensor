<?php 
class Ps_ORMHelper extends DevblocksORMHelper {
	static public function qstr($str) {
		$db = DevblocksPlatform::getDatabaseService();
		return $db->qstr($str);	
	}
	
	static protected function _appendSelectJoinSqlForCustomFieldTables($tables, $params, $key, $select_sql, $join_sql) {
		$custom_fields = DAO_CustomField::getAll();
		$field_ids = array();
		
		$return_multiple_values = false; // can our CF return more than one hit? (GROUP BY)
		
		if(is_array($tables))
		foreach($tables as $tbl_name => $null) {
			// Filter and sanitize
			if(substr($tbl_name,0,3) != "cf_" // not a custom field 
				|| 0 == ($field_id = intval(substr($tbl_name,3)))) // not a field_id
				continue;

			// Make sure the field exists for this source
			if(!isset($custom_fields[$field_id]))
				continue; 
			
			$field_table = sprintf("cf_%d", $field_id);
			$value_table = '';
			
			// Join value by field data type
			switch($custom_fields[$field_id]->type) {
				case 'T': // multi-line CLOB
					$value_table = 'custom_field_clobvalue';
					break;
				case 'C': // checkbox
				case 'E': // date
				case 'N': // number
				case 'W': // worker
					$value_table = 'custom_field_numbervalue';
					break;
				default:
				case 'S': // single-line
				case 'D': // dropdown
				case 'U': // URL
					$value_table = 'custom_field_stringvalue';
					break;
			}

			$has_multiple_values = false;
			switch($custom_fields[$field_id]->type) {
				case Model_CustomField::TYPE_MULTI_PICKLIST:
				case Model_CustomField::TYPE_MULTI_CHECKBOX:
					$has_multiple_values = true;
					break;
			}

			// If we have multiple values but we don't need to WHERE the JOIN, be efficient and don't GROUP BY
			if(!isset($params['cf_'.$field_id])) {
				$select_sql .= sprintf(",(SELECT field_value FROM %s WHERE %s=source_id AND field_id=%d LIMIT 0,1) AS %s ",
					$value_table,
					$key,
					$field_id,
					$field_table
				);
				
			} else {
				$select_sql .= sprintf(", %s.field_value as %s ",
					$field_table,
					$field_table
				);
				
				$join_sql .= sprintf("LEFT JOIN %s %s ON (%s=%s.source_id AND %s.field_id=%d) ",
					$value_table,
					$field_table,
					$key,
					$field_table,
					$field_table,
					$field_id
				);
				
				// If we do need to WHERE this JOIN, make sure we GROUP BY
				if($has_multiple_values)
					$return_multiple_values = true;
			}
		}
		
		return array($select_sql, $join_sql, $return_multiple_values);
	}
};

class DAO_CustomField extends DevblocksORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const TYPE = 'type';
	const SOURCE_EXTENSION = 'source_extension';
	const POS = 'pos';
	const OPTIONS = 'options';
	
	const CACHE_ALL = 'ps_customfields'; 
	
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('custom_field_seq');
		
		$sql = sprintf("INSERT INTO custom_field (id,name,type,source_extension,pos,options) ".
			"VALUES (%d,'','','',0,'')",
			$id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'custom_field', $fields);
		
		self::clearCache();
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return Model_CustomField|null
	 */
	static function get($id) {
		$fields = self::getAll();
		
		if(isset($fields[$id]))
			return $fields[$id];
			
		return null;
	}
	
	static function getBySource($source_ext_id) {
		$fields = self::getAll();
		
		// Filter fields to only the requested source
		foreach($fields as $idx => $field) { /* @var $field Model_CustomField */
			if(0 != strcasecmp($field->source_extension, $source_ext_id))
				unset($fields[$idx]);
		}
		
		return $fields;
	}
	
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::getCacheService();
		
		if(null === ($objects = $cache->load(self::CACHE_ALL))) {
			$db = DevblocksPlatform::getDatabaseService();
			$sql = "SELECT id, name, type, source_extension, pos, options ".
				"FROM custom_field ".
				"ORDER BY pos ASC "
			;
			$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
			
			$objects = self::_createObjectsFromResultSet($rs);
			
			$cache->save($objects, self::CACHE_ALL);
		}
		
		return $objects;
	}
	
	private static function _createObjectsFromResultSet(ADORecordSet $rs) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$objects = array();
		
		if($rs instanceof ADORecordSet)
		while(!$rs->EOF) {
			$object = new Model_CustomField();
			$object->id = intval($rs->fields['id']);
			$object->name = $rs->fields['name'];
			$object->type = $rs->fields['type'];
			$object->source_extension = $rs->fields['source_extension'];
			$object->pos = intval($rs->fields['pos']);
			$object->options = DevblocksPlatform::parseCrlfString($rs->fields['options']);
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	public static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		if(empty($ids))
			return;
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$id_string = implode(',', $ids);
		
		$sql = sprintf("DELETE QUICK FROM custom_field WHERE id IN (%s)",$id_string);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		if(is_array($ids))
		foreach($ids as $id) {
			DAO_CustomFieldValue::deleteByFieldId($id);
		}
		
		self::clearCache();
	}
	
	public static function clearCache() {
		// Invalidate cache on changes
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}
};

class DAO_CustomFieldValue extends DevblocksORMHelper {
	const FIELD_ID = 'field_id';
	const SOURCE_EXTENSION = 'source_extension';
	const SOURCE_ID = 'source_id';
	const FIELD_VALUE = 'field_value';
	
	public static function getValueTableName($field_id) {
		$field = DAO_CustomField::get($field_id);
		
		// Determine value table by type
		$table = null;
		switch($field->type) {
			// stringvalue
			case Model_CustomField::TYPE_SINGLE_LINE:
			case Model_CustomField::TYPE_DROPDOWN:	
			case Model_CustomField::TYPE_MULTI_CHECKBOX:	
			case Model_CustomField::TYPE_MULTI_PICKLIST:
			case Model_CustomField::TYPE_URL:
				$table = 'custom_field_stringvalue';	
				break;
			// clobvalue
			case Model_CustomField::TYPE_MULTI_LINE:
				$table = 'custom_field_clobvalue';
				break;
			// number
			case Model_CustomField::TYPE_CHECKBOX:
			case Model_CustomField::TYPE_DATE:
			case Model_CustomField::TYPE_NUMBER:
			case Model_CustomField::TYPE_WORKER:
				$table = 'custom_field_numbervalue';
				break;	
		}
		
		return $table;
	}
	
	/**
	 * 
	 * @param object $source_ext_id
	 * @param object $source_id
	 * @param object $values
	 * @return 
	 */
	public static function formatAndSetFieldValues($source_ext_id, $source_id, $values, $is_blank_unset=true) {
		if(empty($source_ext_id) || empty($source_id) || !is_array($values))
			return;

		$fields = DAO_CustomField:: getBySource($source_ext_id);

		foreach($values as $field_id => $value) {
			if(!isset($fields[$field_id]))
				continue;

			$field =& $fields[$field_id]; /* @var $field Model_CustomField */
			$delta = ($field->type==Model_CustomField::TYPE_MULTI_CHECKBOX || $field->type==Model_CustomField::TYPE_MULTI_PICKLIST) 
					? true 
					: false
					;

			// if the field is blank
			if(0==strlen($value)) {
				// ... and blanks should unset
				if($is_blank_unset && !$delta)
					self::unsetFieldValue($source_ext_id, $source_id, $field_id);
				
				// Skip setting
				continue;
			}

			switch($field->type) {
				case Model_CustomField::TYPE_SINGLE_LINE:
				case Model_CustomField::TYPE_URL:
					$value = (strlen($value) > 255) ? substr($value,0,255) : $value;
					self::setFieldValue($source_ext_id, $source_id, $field_id, $value);
					break;

				case Model_CustomField::TYPE_MULTI_LINE:
					self::setFieldValue($source_ext_id, $source_id, $field_id, $value);
					break;

				case Model_CustomField::TYPE_DROPDOWN:
				case Model_CustomField::TYPE_MULTI_PICKLIST:
				case Model_CustomField::TYPE_MULTI_CHECKBOX:
					// If we're setting a field that doesn't exist yet, add it.
					if(!in_array($value,$field->options) && !empty($value)) {
						$field->options[] = $value;
						DAO_CustomField::update($field_id, array(DAO_CustomField::OPTIONS => implode("\n",$field->options)));
					}

					// If we're allowed to add/remove fields without touching the rest
					self::setFieldValue($source_ext_id, $source_id, $field_id, $value, $delta);
						
					break;

				case Model_CustomField::TYPE_CHECKBOX:
					$value = !empty($value) ? 1 : 0;
					self::setFieldValue($source_ext_id, $source_id, $field_id, $value);
					break;

				case Model_CustomField::TYPE_DATE:
					@$value = strtotime($value);
					self::setFieldValue($source_ext_id, $source_id, $field_id, $value);
					break;

				case Model_CustomField::TYPE_NUMBER:
					$value = intval($value);
					self::setFieldValue($source_ext_id, $source_id, $field_id, $value);
					break;
					
				case Model_CustomField::TYPE_WORKER:
					$value = intval($value);
					self::setFieldValue($source_ext_id, $source_id, $field_id, $value);
					break;
			}
		}
		
	}
	
	public static function setFieldValue($source_ext_id, $source_id, $field_id, $value, $delta=false) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(null == ($field = DAO_CustomField::get($field_id)))
			return FALSE;
		
		if(null == ($table_name = self::getValueTableName($field_id)))
			return FALSE;

		// Data formating
		switch($field->type) {
			case 'D': // dropdown
			case 'S': // string
			case 'U': // URL
				if(255 < strlen($value))
					$value = substr($value,0,255);
				break;
			case 'N': // number
			case 'W': // worker
				$value = intval($value);
		}
		
		// Clear existing values (beats replace logic)
		self::unsetFieldValue($source_ext_id, $source_id, $field_id, ($delta?$value:null));

		// Set values consistently
		if(!is_array($value))
			$value = array($value);
			
		foreach($value as $v) {
			$sql = sprintf("INSERT INTO %s (field_id, source_extension, source_id, field_value) ".
				"VALUES (%d, %s, %d, %s)",
				$table_name,
				$field_id,
				$db->qstr($source_ext_id),
				$source_id,
				$db->qstr($v)
			);
			$db->Execute($sql);
		}
		
		return TRUE;
	}
	
	public static function unsetFieldValue($source_ext_id, $source_id, $field_id, $value=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(null == ($field = DAO_CustomField::get($field_id)))
			return FALSE;
		
		if(null == ($table_name = self::getValueTableName($field_id)))
			return FALSE;
		
		// Delete all values or optionally a specific given value
		$sql = sprintf("DELETE QUICK FROM %s WHERE source_extension = '%s' AND source_id = %d AND field_id = %d %s",
			$table_name,
			$source_ext_id,
			$source_id,
			$field_id,
			(!is_null($value) ? sprintf("AND field_value = %s ",$db->qstr($value)) : "")
		);
		
		return $db->Execute($sql);
	}
	
	public static function handleBulkPost($do) {
		@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'],'array',array());

		$fields = DAO_CustomField::getAll();
		
		if(is_array($field_ids))
		foreach($field_ids as $field_id) {
			if(!isset($fields[$field_id]))
				continue;
			
			switch($fields[$field_id]->type) {
				case Model_CustomField::TYPE_MULTI_LINE:
				case Model_CustomField::TYPE_SINGLE_LINE:
				case Model_CustomField::TYPE_URL:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
				case Model_CustomField::TYPE_NUMBER:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					$field_value = (0==strlen($field_value)) ? '' : intval($field_value);
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
				case Model_CustomField::TYPE_DROPDOWN:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
				case Model_CustomField::TYPE_MULTI_PICKLIST:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'array',array());
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
				case Model_CustomField::TYPE_CHECKBOX:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'integer',0);
					$do['cf_'.$field_id] = array('value' => !empty($field_value) ? 1 : 0);
					break;

				case Model_CustomField::TYPE_MULTI_CHECKBOX:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'array',array());
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
				case Model_CustomField::TYPE_DATE:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
				case Model_CustomField::TYPE_WORKER:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
			}
		}
		
		return $do;
	}
	
	public static function handleFormPost($source_ext_id, $source_id, $field_ids) {
		$fields = DAO_CustomField::getBySource($source_ext_id);
		
		if(is_array($field_ids))
		foreach($field_ids as $field_id) {
			if(!isset($fields[$field_id]))
				continue;
			
			switch($fields[$field_id]->type) {
				case Model_CustomField::TYPE_MULTI_LINE:
				case Model_CustomField::TYPE_SINGLE_LINE:
				case Model_CustomField::TYPE_URL:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					if(0 != strlen($field_value)) {
						DAO_CustomFieldValue::setFieldValue($source_ext_id, $source_id, $field_id, $field_value);
					} else {
						DAO_CustomFieldValue::unsetFieldValue($source_ext_id, $source_id, $field_id);
					}
					break;
					
				case Model_CustomField::TYPE_DROPDOWN:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					if(0 != strlen($field_value)) {
						DAO_CustomFieldValue::setFieldValue($source_ext_id, $source_id, $field_id, $field_value);
					} else {
						DAO_CustomFieldValue::unsetFieldValue($source_ext_id, $source_id, $field_id);
					}
					break;
					
				case Model_CustomField::TYPE_MULTI_PICKLIST:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'array',array());
					if(!empty($field_value)) {
						DAO_CustomFieldValue::setFieldValue($source_ext_id, $source_id, $field_id, $field_value);
					} else {
						DAO_CustomFieldValue::unsetFieldValue($source_ext_id, $source_id, $field_id);
					}
					break;
					
				case Model_CustomField::TYPE_CHECKBOX:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'integer',0);
					$set = !empty($field_value) ? 1 : 0;
					DAO_CustomFieldValue::setFieldValue($source_ext_id, $source_id, $field_id, $set);
					break;

				case Model_CustomField::TYPE_MULTI_CHECKBOX:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'array',array());
					if(!empty($field_value)) {
						DAO_CustomFieldValue::setFieldValue($source_ext_id, $source_id, $field_id, $field_value);
					} else {
						DAO_CustomFieldValue::unsetFieldValue($source_ext_id, $source_id, $field_id);
					}
					break;
				
				case Model_CustomField::TYPE_DATE:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					@$date = strtotime($field_value);
					if(!empty($date)) {
						DAO_CustomFieldValue::setFieldValue($source_ext_id, $source_id, $field_id, $date);
					} else {
						DAO_CustomFieldValue::unsetFieldValue($source_ext_id, $source_id, $field_id);
					}
					break;

				case Model_CustomField::TYPE_NUMBER:
				case Model_CustomField::TYPE_WORKER:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					if(0 != strlen($field_value)) {
						DAO_CustomFieldValue::setFieldValue($source_ext_id, $source_id, $field_id, intval($field_value));
					} else {
						DAO_CustomFieldValue::unsetFieldValue($source_ext_id, $source_id, $field_id);
					}
					break;
			}
		}
		
		return true;
	}
	
	public static function getValuesBySourceIds($source_ext_id, $source_ids) {
		if(!is_array($source_ids)) $source_ids = array($source_ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$results = array();
		
		if(empty($source_ids))
			return array();
		
		$fields = DAO_CustomField::getAll();
			
		// [TODO] This is inefficient (and redundant)
			
		// STRINGS
		$sql = sprintf("SELECT source_id, field_id, field_value ".
			"FROM custom_field_stringvalue ".
			"WHERE source_extension = '%s' AND source_id IN (%s)",
			$source_ext_id,
			implode(',', $source_ids)
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$source_id = intval($rs->fields['source_id']);
			$field_id = intval($rs->fields['field_id']);
			$field_value = $rs->fields['field_value'];
			
			if(!isset($results[$source_id]))
				$results[$source_id] = array();
				
			$source =& $results[$source_id];
			
			// If multiple value type (multi-picklist, multi-checkbox)
			if($fields[$field_id]->type=='M' || $fields[$field_id]->type=='X') {
				if(!isset($source[$field_id]))
					$source[$field_id] = array();
					
				$source[$field_id][$field_value] = $field_value;
				
			} else { // single value
				$source[$field_id] = $field_value;
				
			}
			
			$rs->MoveNext();
		}
		
		// CLOBS
		$sql = sprintf("SELECT source_id, field_id, field_value ".
			"FROM custom_field_clobvalue ".
			"WHERE source_extension = '%s' AND source_id IN (%s)",
			$source_ext_id,
			implode(',', $source_ids)
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$source_id = intval($rs->fields['source_id']);
			$field_id = intval($rs->fields['field_id']);
			$field_value = $rs->fields['field_value'];
			
			if(!isset($results[$source_id]))
				$results[$source_id] = array();
				
			$source =& $results[$source_id];
			$source[$field_id] = $field_value;
			
			$rs->MoveNext();
		}

		// NUMBERS
		$sql = sprintf("SELECT source_id, field_id, field_value ".
			"FROM custom_field_numbervalue ".
			"WHERE source_extension = '%s' AND source_id IN (%s)",
			$source_ext_id,
			implode(',', $source_ids)
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$source_id = intval($rs->fields['source_id']);
			$field_id = intval($rs->fields['field_id']);
			$field_value = $rs->fields['field_value'];
			
			if(!isset($results[$source_id]))
				$results[$source_id] = array();
				
			$source =& $results[$source_id];
			$source[$field_id] = $field_value;
			
			$rs->MoveNext();
		}
		
		return $results;
	}
	
	public static function deleteBySourceIds($source_extension, $source_ids) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(!is_array($source_ids)) $source_ids = array($source_ids);
		$ids_list = implode(',', $source_ids);

		$tables = array('custom_field_stringvalue','custom_field_clobvalue','custom_field_numbervalue');
		
		if(!empty($source_ids))
		foreach($tables as $table) {
			$sql = sprintf("DELETE QUICK FROM %s WHERE source_extension = %s AND source_id IN (%s)",
				$table,
				$db->qstr($source_extension),
				implode(',', $source_ids)
			);
			$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		}
	}
	
	public static function deleteByFieldId($field_id) {
		$db = DevblocksPlatform::getDatabaseService();

		$tables = array('custom_field_stringvalue','custom_field_clobvalue','custom_field_numbervalue');

		foreach($tables as $table) {
			$sql = sprintf("DELETE QUICK FROM %s WHERE field_id = %d",
				$table,
				$field_id
			);
			$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		}

	}
};

/**
 * Global Settings DAO
 */
class DAO_Setting extends DevblocksORMHelper {
	static function set($key, $value) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->Replace('setting',array('setting'=>$db->qstr($key),'value'=>$db->qstr($value)),array('setting'),false);
	}
	
	static function get($key) {
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("SELECT value FROM setting WHERE setting = %s",
			$db->qstr($key)
		);
		$value = $db->GetOne($sql) or die(__CLASS__ . ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $value;
	}
	
	static function getSettings() {
	    $cache = DevblocksPlatform::getCacheService();
	    if(null === ($settings = $cache->load(PortSensorApplication::CACHE_SETTINGS_DAO))) {
			$db = DevblocksPlatform::getDatabaseService();
			$settings = array();
			
			$sql = sprintf("SELECT setting,value FROM setting");
			$rs = $db->Execute($sql) or die(__CLASS__ . ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
			
			if(is_a($rs,'ADORecordSet'))
			while(!$rs->EOF) {
				$settings[$rs->Fields('setting')] = $rs->Fields('value');
				$rs->MoveNext();
			}
			
			$cache->save($settings, PortSensorApplication::CACHE_SETTINGS_DAO);
	    }
		
		return $settings;
	}
};

class DAO_Worker extends Ps_ORMHelper {
	const CACHE_ALL = 'ps_workers';
	
	const ID = 'id';
	const FIRST_NAME = 'first_name';
	const LAST_NAME = 'last_name';
	const TITLE = 'title';
	const EMAIL = 'email';
	const PASS = 'pass';
	const IS_SUPERUSER = 'is_superuser';
	const LAST_ACTIVITY_DATE = 'last_activity_date';
	const LAST_ACTIVITY = 'last_activity';
	const IS_DISABLED = 'is_disabled';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('worker_seq');
		
		$sql = sprintf("INSERT INTO worker (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $flush_cache=true) {
		parent::_update($ids, 'worker', $fields);
		
		if($flush_cache) {
			self::clearCache();
		}
	}
	
	static function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}
	
	static function getAllActive() {
		return self::getAll(false, false);
	}
	
	static function getAllWithDisabled() {
		return self::getAll(false, true);
	}
	
	static function getAllOnline() {
		list($whos_online_workers, $null) = self::search(
			array(),
		    array(
		        new DevblocksSearchCriteria(SearchFields_Worker::LAST_ACTIVITY_DATE,DevblocksSearchCriteria::OPER_GT,(time()-60*15)), // idle < 15 mins
		        new DevblocksSearchCriteria(SearchFields_Worker::LAST_ACTIVITY,DevblocksSearchCriteria::OPER_NOT_LIKE,'%translation_code";N;%'), // translation code not null (not just logged out)
		    ),
		    -1,
		    0,
		    SearchFields_Worker::LAST_ACTIVITY_DATE,
		    false,
		    false
		);
		
		if(!empty($whos_online_workers))
			return self::getWhere(
				sprintf("%s IN (%s)",
					DAO_Worker::ID,
					implode(',',array_keys($whos_online_workers))
				));
			
		return array();
	}
	
	static function getAll($nocache=false, $with_disabled=true) {
	    $cache = DevblocksPlatform::getCacheService();
	    if($nocache || null === ($workers = $cache->load(self::CACHE_ALL))) {
    	    $workers = self::getWhere();
    	    $cache->save($workers, self::CACHE_ALL);
	    }
	    
	    /*
	     * If the caller doesn't want disabled workers then remove them from the results,
	     * but don't bother caching two different versions (always cache all)
	     */
	    if(!$with_disabled) {
	    	foreach($workers as $worker_id => $worker) { /* @var $worker CerberusWorker */
	    		if($worker->is_disabled)
	    			unset($workers[$worker_id]);
	    	}
	    }
	    
	    return $workers;
	}	
	
	/**
	 * @param string $where
	 * @return Model_Worker[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, first_name, last_name, title, email, pass, is_superuser, last_activity_date, last_activity, is_disabled ".
			"FROM worker ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_Worker	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * @param ADORecordSet $rs
	 * @return Model_Worker[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while(!$rs->EOF) {
			$object = new Model_Worker();
			$object->id = $rs->fields['id'];
			$object->first_name = $rs->fields['first_name'];
			$object->last_name = $rs->fields['last_name'];
			$object->title = $rs->fields['title'];
			$object->email = $rs->fields['email'];
			$object->pass = $rs->fields['pass'];
			$object->is_superuser = $rs->fields['is_superuser'];
			$object->last_activity_date = $rs->fields['last_activity_date'];
			$object->is_disabled = $rs->fields['is_disabled'];

			if(!empty($rs->fields['last_activity']))
			    $object->last_activity = unserialize($rs->fields['last_activity']);
			
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM worker WHERE id IN (%s)", $ids_list));
		
		return true;
	}
	
	static function login($email, $password) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$where = sprintf("%s = %s AND %s = %s",
				self::EMAIL,
				$db->qstr($email),
				self::PASS,
				$db->qstr(md5($password))
			);
		
		$results = self::getWhere($where);
		
		if(!empty($results))
			return array_shift($results);
			
		return NULL;
	}
	
	/**
	 * Store the workers last activity (provided by the page extension).
	 * 
	 * @param integer $worker_id
	 * @param Model_Activity $activity
	 */
	static function logActivity($worker_id, Model_Activity $activity) {
	    DAO_Worker::update($worker_id,array(
	        DAO_Worker::LAST_ACTIVITY_DATE => time(),
	        DAO_Worker::LAST_ACTIVITY => serialize($activity)
	    ),false);
	}

    /**
     * Enter description here...
     *
     * @param DevblocksSearchCriteria[] $params
     * @param integer $limit
     * @param integer $page
     * @param string $sortBy
     * @param boolean $sortAsc
     * @param boolean $withCounts
     * @return array
     */
    static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();
		$fields = SearchFields_Worker::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		$total = -1;
		
		$select_sql = sprintf("SELECT ".
			"w.id as %s, ".
			"w.first_name as %s, ".
			"w.last_name as %s, ".
			"w.title as %s, ".
			"w.email as %s, ".
			"w.is_superuser as %s, ".
			"w.last_activity_date as %s, ".
			"w.is_disabled as %s ",
			    SearchFields_Worker::ID,
			    SearchFields_Worker::FIRST_NAME,
			    SearchFields_Worker::LAST_NAME,
			    SearchFields_Worker::TITLE,
			    SearchFields_Worker::EMAIL,
			    SearchFields_Worker::IS_SUPERUSER,
			    SearchFields_Worker::LAST_ACTIVITY_DATE,
			    SearchFields_Worker::IS_DISABLED
			);
			
		$join_sql = "FROM worker w ";
		
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'w.id',
			$select_sql,
			$join_sql
		);
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
			
		$sql = 
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY w.id ' : '').
			$sort_sql;
			
		// [TODO] Could push the select logic down a level too
		if($limit > 0) {
    		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		} else {
		    $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
            $total = $rs->RecordCount();
		}
		
		$results = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$result = array();
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$object_id = intval($rs->fields[SearchFields_Worker::ID]);
			$results[$object_id] = $result;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT w.id) " : "SELECT COUNT(w.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		return array($results,$total);
    }

};

class SearchFields_Worker implements IDevblocksSearchFields {
	// Worker
	const ID = 'w_id';
	const FIRST_NAME = 'w_first_name';
	const LAST_NAME = 'w_last_name';
	const TITLE = 'w_title';
	const EMAIL = 'w_email';
	const IS_SUPERUSER = 'w_is_superuser';
	const LAST_ACTIVITY = 'w_last_activity';
	const LAST_ACTIVITY_DATE = 'w_last_activity_date';
	const IS_DISABLED = 'w_is_disabled';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'w', 'id', null, $translate->_('common.id')),
			self::FIRST_NAME => new DevblocksSearchField(self::FIRST_NAME, 'w', 'first_name', null, $translate->_('worker.first_name')),
			self::LAST_NAME => new DevblocksSearchField(self::LAST_NAME, 'w', 'last_name', null, $translate->_('worker.last_name')),
			self::TITLE => new DevblocksSearchField(self::TITLE, 'w', 'title', null, $translate->_('worker.title')),
			self::EMAIL => new DevblocksSearchField(self::EMAIL, 'w', 'email', null, ucwords($translate->_('common.email'))),
			self::IS_SUPERUSER => new DevblocksSearchField(self::IS_SUPERUSER, 'w', 'is_superuser', null, $translate->_('worker.is_superuser')),
			self::LAST_ACTIVITY => new DevblocksSearchField(self::LAST_ACTIVITY, 'w', 'last_activity', null, $translate->_('worker.last_activity')),
			self::LAST_ACTIVITY_DATE => new DevblocksSearchField(self::LAST_ACTIVITY_DATE, 'w', 'last_activity_date', null, $translate->_('worker.last_activity_date')),
			self::IS_DISABLED => new DevblocksSearchField(self::IS_DISABLED, 'w', 'is_disabled', null, ucwords($translate->_('common.disabled'))),
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getBySource(PsCustomFieldSource_Worker::ID);

		if(is_array($fields))
		foreach($fields as $field_id => $field) {
			$key = 'cf_'.$field_id;
			$columns[$key] = new DevblocksSearchField($key,$key,'field_value',null,$field->name);
		}
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));

		return $columns;		
	}
};

class DAO_WorkerPref extends DevblocksORMHelper {
    const CACHE_PREFIX = 'ps_workerpref_';
    
	static function set($worker_id, $key, $value) {
		// Persist long-term
		$db = DevblocksPlatform::getDatabaseService();
		$result = $db->Replace(
		    'worker_pref',
		    array(
		        'worker_id'=>$worker_id,
		        'setting'=>$db->qstr($key),
		        'value'=>$db->qstr($value)
		    ),
		    array('worker_id','setting'),
		    false
		);
		
		// Invalidate cache
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_PREFIX.$worker_id);
	}
	
	static function get($worker_id, $key, $default=null) {
		$value = null;
		
		if(null !== ($worker_prefs = self::getByWorker($worker_id))) {
			if(isset($worker_prefs[$key])) {
				$value = $worker_prefs[$key];
			}
		}
		
		if(null === $value && !is_null($default)) {
		    return $default;
		}
		
		return $value;
	}

	static function getByWorker($worker_id) {
		$cache = DevblocksPlatform::getCacheService();
		
		if(null === ($objects = $cache->load(self::CACHE_PREFIX.$worker_id))) {
			$db = DevblocksPlatform::getDatabaseService();
			$sql = sprintf("SELECT setting, value FROM worker_pref WHERE worker_id = %d", $worker_id);
			$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
			
			$objects = array();
			
			if(is_a($rs,'ADORecordSet'))
			while(!$rs->EOF) {
			    $objects[$rs->fields['setting']] = $rs->fields['value'];
			    $rs->MoveNext();
			}
			
			$cache->save($objects, self::CACHE_PREFIX.$worker_id);
		}
		
		return $objects;
	}
};

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
|	http://www.portsensor.com	  http://www.webgroupmedia.com/
***********************************************************************/
$db = DevblocksPlatform::getDatabaseService();
$datadict = NewDataDictionary($db); /* @var $datadict ADODB_DataDict */ // ,'mysql' 

$tables = $datadict->MetaTables();
$tables = array_flip($tables);

// ***** Application

// `setting` =============================
if(!isset($tables['setting'])) {
    $flds = "
		setting C(32) DEFAULT '' NOTNULL PRIMARY,
		value C(255) DEFAULT '' NOTNULL
    ";
    $sql = $datadict->CreateTableSQL('setting', $flds);
    $datadict->ExecuteSQLArray($sql);
}

$columns = $datadict->MetaColumns('setting');
$indexes = $datadict->MetaIndexes('setting',false);

// `worker` =============================
if(!isset($tables['worker'])) {
    $flds = "
		id I2 DEFAULT 0 NOTNULL PRIMARY,
		first_name C(255) DEFAULT '' NOTNULL,
		last_name C(255) DEFAULT '' NOTNULL,
		title C(255) DEFAULT '' NOTNULL,
		email C(255) DEFAULT '' NOTNULL,
		pass C(32) DEFAULT '' NOTNULL,
		is_superuser I1 DEFAULT 0 NOTNULL,
		last_activity_date I4 DEFAULT 0 NOTNULL,
		last_activity XL,
		is_disabled I1 DEFAULT 0 NOTNULL
    ";
    $sql = $datadict->CreateTableSQL('worker', $flds);
    $datadict->ExecuteSQLArray($sql);
}

$columns = $datadict->MetaColumns('worker');
$indexes = $datadict->MetaIndexes('worker',false);

if(!isset($indexes['last_activity_date'])) {
	$sql = $datadict->CreateIndexSQL('last_activity_date','worker','last_activity_date');
	$datadict->ExecuteSQLArray($sql);
}

// `worker_pref` =============================
if(!isset($tables['worker_pref'])) {
    $flds = "
		worker_id I2 DEFAULT 0 NOTNULL PRIMARY,
		setting C(255) DEFAULT '' NOTNULL PRIMARY,
		value XL
    ";
    $sql = $datadict->CreateTableSQL('worker_pref', $flds);
    $datadict->ExecuteSQLArray($sql);
}

// `custom_field` =============================
if(!isset($tables['custom_field'])) {
    $flds = "
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		name C(255) DEFAULT '' NOTNULL,
		type C(1) DEFAULT 'S' NOTNULL,
		pos I2 DEFAULT 0 NOTNULL,
		options XL,
		source_extension C(255) DEFAULT '' NOTNULL
    ";
    $sql = $datadict->CreateTableSQL('custom_field', $flds);
    $datadict->ExecuteSQLArray($sql);
}

$columns = $datadict->MetaColumns('custom_field');
$indexes = $datadict->MetaIndexes('custom_field',false);

if(!isset($indexes['pos'])) {
	$sql = $datadict->CreateIndexSQL('pos','custom_field','pos');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['source_extension'])) {
	$sql = $datadict->CreateIndexSQL('source_extension','custom_field','source_extension');
	$datadict->ExecuteSQLArray($sql);
}

// `custom_field_clobvalue` =============================
if(!isset($tables['custom_field_clobvalue'])) {
    $flds = "
		field_id I4 DEFAULT 0 NOTNULL,
		source_id I4 DEFAULT 0 NOTNULL,
		field_value XL,
		source_extension C(255) DEFAULT '' NOTNULL
    ";
    $sql = $datadict->CreateTableSQL('custom_field_clobvalue', $flds);
    $datadict->ExecuteSQLArray($sql);
}

$columns = $datadict->MetaColumns('custom_field_clobvalue');
$indexes = $datadict->MetaIndexes('custom_field_clobvalue',false);

if(!isset($indexes['field_id'])) {
	$sql = $datadict->CreateIndexSQL('field_id','custom_field_clobvalue','field_id');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['source_id'])) {
	$sql = $datadict->CreateIndexSQL('source_id','custom_field_clobvalue','source_id');
	$datadict->ExecuteSQLArray($sql);
}

// `custom_field_numbervalue` =============================
if(!isset($tables['custom_field_clobvalue'])) {
    $flds = "
		field_id I4 DEFAULT 0 NOTNULL,
		source_id I4 DEFAULT 0 NOTNULL,
		field_value I4 DEFAULT 0 NOTNULL,
		source_extension C(255) DEFAULT '' NOTNULL
    ";
    $sql = $datadict->CreateTableSQL('custom_field_numbervalue', $flds);
    $datadict->ExecuteSQLArray($sql);
}

$columns = $datadict->MetaColumns('custom_field_numbervalue');
$indexes = $datadict->MetaIndexes('custom_field_numbervalue',false);

if(!isset($indexes['field_id'])) {
	$sql = $datadict->CreateIndexSQL('field_id','custom_field_numbervalue','field_id');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['source_id'])) {
	$sql = $datadict->CreateIndexSQL('source_id','custom_field_numbervalue','source_id');
	$datadict->ExecuteSQLArray($sql);
}
// `custom_field_stringvalue` =============================
if(!isset($tables['custom_field_stringvalue'])) {
    $flds = "
		field_id I4 DEFAULT 0 NOTNULL,
		source_id I4 DEFAULT 0 NOTNULL,
		field_value C(255) DEFAULT '' NOTNULL,
		source_extension C(255) DEFAULT '' NOTNULL
    ";
    $sql = $datadict->CreateTableSQL('custom_field_stringvalue', $flds);
    $datadict->ExecuteSQLArray($sql);
}

$columns = $datadict->MetaColumns('custom_field_stringvalue');
$indexes = $datadict->MetaIndexes('custom_field_stringvalue',false);

if(!isset($indexes['field_id'])) {
	$sql = $datadict->CreateIndexSQL('field_id','custom_field_stringvalue','field_id');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['source_id'])) {
	$sql = $datadict->CreateIndexSQL('source_id','custom_field_stringvalue','source_id');
	$datadict->ExecuteSQLArray($sql);
}

// `worker_role` =============================
if(!isset($tables['worker_role'])) {
    $flds = "
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		name C(255) DEFAULT '' NOTNULL
    ";
    $sql = $datadict->CreateTableSQL('worker_role', $flds);
    $datadict->ExecuteSQLArray($sql);
}

// `worker_role_acl` =============================
if(!isset($tables['worker_role_acl'])) {
    $flds = "
		role_id I4 DEFAULT 0 NOTNULL,
		priv_id C(255) DEFAULT '' NOTNULL,
		has_priv I1 DEFAULT 0 NOTNULL
    ";
    $sql = $datadict->CreateTableSQL('worker_role_acl', $flds);
    $datadict->ExecuteSQLArray($sql);
}

$columns = $datadict->MetaColumns('worker_role_acl');
$indexes = $datadict->MetaIndexes('worker_role_acl',false);

if(!isset($indexes['role_id'])) {
	$sql = $datadict->CreateIndexSQL('role_id','worker_role_acl','role_id');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['priv_id'])) {
	$sql = $datadict->CreateIndexSQL('priv_id','worker_role_acl','priv_id');
	$datadict->ExecuteSQLArray($sql);
}

// `worker_to_role` =============================
if(!isset($tables['worker_to_role'])) {
    $flds = "
		worker_id I4 DEFAULT 0 NOTNULL,
		role_id I4 DEFAULT 0 NOTNULL
    ";
    $sql = $datadict->CreateTableSQL('worker_to_role', $flds);
    $datadict->ExecuteSQLArray($sql);
}

$columns = $datadict->MetaColumns('worker_to_role');
$indexes = $datadict->MetaIndexes('worker_to_role',false);

if(!isset($indexes['role_id'])) {
	$sql = $datadict->CreateIndexSQL('role_id','worker_to_role','role_id');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['worker_id'])) {
	$sql = $datadict->CreateIndexSQL('worker_id','worker_to_role','worker_id');
	$datadict->ExecuteSQLArray($sql);
}

// `worker_event` =============================
if(!isset($tables['worker_event'])) {
    $flds = "
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		created_date I4 DEFAULT 0 NOTNULL,
		worker_id I4 DEFAULT 0 NOTNULL,
		title C(255) DEFAULT '' NOTNULL,
		content XL,
		is_read I1 DEFAULT 0 NOTNULL,
		url C(255) DEFAULT '' NOTNULL
    ";
    $sql = $datadict->CreateTableSQL('worker_event', $flds);
    $datadict->ExecuteSQLArray($sql);
}

$columns = $datadict->MetaColumns('worker_event');
$indexes = $datadict->MetaIndexes('worker_event',false);

if(!isset($indexes['created_date'])) {
	$sql = $datadict->CreateIndexSQL('created_date','worker_event','created_date');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['worker_id'])) {
	$sql = $datadict->CreateIndexSQL('worker_id','worker_event','worker_id');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['is_read'])) {
	$sql = $datadict->CreateIndexSQL('is_read','worker_event','is_read');
	$datadict->ExecuteSQLArray($sql);
}

// `sensor` =============================
if(!isset($tables['sensor'])) {
    $flds = "
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		name C(255) DEFAULT '' NOTNULL,
		extension_id C(255) DEFAULT '' NOTNULL,
		params XL,
		status I1 DEFAULT 0 NOTNULL,
		updated_date I4 DEFAULT 0 NOTNULL,
		is_disabled I1 DEFAULT 0 NOTNULL,
		metric_type C(1) DEFAULT 'T' NOTNULL,
		metric XL,
		output XL,
		fail_count I1 DEFAULT 0 NOTNULL
    ";
    $sql = $datadict->CreateTableSQL('sensor', $flds);
    $datadict->ExecuteSQLArray($sql);
}

$columns = $datadict->MetaColumns('sensor');
$indexes = $datadict->MetaIndexes('sensor',false);

if(!isset($indexes['updated_date'])) {
	$sql = $datadict->CreateIndexSQL('updated_date','sensor','updated_date');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['status'])) {
	$sql = $datadict->CreateIndexSQL('status','sensor','status');
	$datadict->ExecuteSQLArray($sql);
}

if(!isset($indexes['is_disabled'])) {
	$sql = $datadict->CreateIndexSQL('is_disabled','sensor','is_disabled');
	$datadict->ExecuteSQLArray($sql);
}

return TRUE;

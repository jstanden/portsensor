<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmAlertFilter" onsubmit="return false;">
<input type="hidden" name="c" value="preferences">
<input type="hidden" name="a" value="saveAlertPeek">
<input type="hidden" name="id" value="{$alert->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">

<b>Alert Name:</b> (e.g. Weekend Emergencies to SMS)<br>
<input type="text" name="name" value="{$alert->name|escape}" size="45" style="width:95%;"><br>

{if $active_worker->is_superuser}
	{'common.worker'|devblocks_translate|capitalize}:
	<select name="worker_id" onchange="genericAjaxGet('div_do_email','c=preferences&a=getWorkerAddresses&worker_id='+selectValue(this));">
		{foreach from=$all_workers item=worker key=worker_id}
			<option value="{$worker_id}" {if (empty($alert->worker_id) && $worker_id==$active_worker->id) || $alert->worker_id==$worker_id}selected="selected"{/if}>{$worker->getName()}</option>
		{/foreach}
	</select>
	 &nbsp; 
{else}
	<input type="hidden" name="worker_id" value="{if !empty($alert->worker_id)}{$alert->worker_id}{else}{$active_worker->id}{/if}">
{/if}

<label><input type="checkbox" name="is_disabled" value="1" {if $alert->is_disabled}checked="checked"{/if}> {'common.disabled'|devblocks_translate|capitalize}</label>
<br>
<br>

<h2>In these sensor events:</h2>
{assign var=crit_event value=$alert->criteria.event}
<input type="hidden" name="rules[]" value="event">
<label><input type="checkbox" name="value_event[]" value="ok" {if isset($crit_event.ok)}checked="checked"{/if}> <span class="status_ok"><b>{'sensor.status.ok'|devblocks_translate|upper}</b></span></label>
<label><input type="checkbox" name="value_event[]" value="warning" {if isset($crit_event.warning)}checked="checked"{/if}> <span class="status_warning"><b>{'sensor.status.warning'|devblocks_translate|upper}</b></span></label>
<label><input type="checkbox" name="value_event[]" value="critical" {if isset($crit_event.critical)}checked="checked"{/if}> <span class="status_critical"><b>{'sensor.status.critical'|devblocks_translate|upper}</b></span></label>
<br>
<br>

<h2>If these criteria match:</h2>

{* Date/Time *}
{assign var=expanded value=false}
{if isset($alert->criteria.dayofweek) || isset($alert->criteria.timeofday)}
	{assign var=expanded value=true}
{/if}
<label><input type="checkbox" {if $expanded}checked="checked"{/if} onclick="toggleDiv('divBlockDateTime',(this.checked?'block':'none'));if(!this.checked)checkAll('divBlockDateTime',false);"> <b>Current Date/Time</b></label><br>
<table width="500" style="margin-left:10px;display:{if $expanded}block{else}none{/if};" id="divBlockDateTime">
	<tr>
		<td valign="top">
			{assign var=crit_dayofweek value=$alert->criteria.dayofweek}
			<label><input type="checkbox" id="chkRuleDayOfWeek" name="rules[]" value="dayofweek" {if !is_null($crit_dayofweek)}checked="checked"{/if}> Day of Week:</label>
		</td>
		<td valign="top">
			<label><input type="checkbox" name="value_dayofweek[]" value="0" {if $crit_dayofweek.sun}checked="checked"{/if}> {'Sunday'|date_format:'%a'}</label>
			<label><input type="checkbox" name="value_dayofweek[]" value="1" {if $crit_dayofweek.mon}checked="checked"{/if}> {'Monday'|date_format:'%a'}</label>
			<label><input type="checkbox" name="value_dayofweek[]" value="2" {if $crit_dayofweek.tue}checked="checked"{/if}> {'Tuesday'|date_format:'%a'}</label>
			<label><input type="checkbox" name="value_dayofweek[]" value="3" {if $crit_dayofweek.wed}checked="checked"{/if}> {'Wednesday'|date_format:'%a'}</label>
			<label><input type="checkbox" name="value_dayofweek[]" value="4" {if $crit_dayofweek.thu}checked="checked"{/if}> {'Thursday'|date_format:'%a'}</label>
			<label><input type="checkbox" name="value_dayofweek[]" value="5" {if $crit_dayofweek.fri}checked="checked"{/if}> {'Friday'|date_format:'%a'}</label>
			<label><input type="checkbox" name="value_dayofweek[]" value="6" {if $crit_dayofweek.sat}checked="checked"{/if}> {'Saturday'|date_format:'%a'}</label>
		</td>
	</tr>
	<tr>
		<td valign="top">
			{assign var=crit_timeofday value=$alert->criteria.timeofday}
			<label><input type="checkbox" id="chkRuleTimeOfDay" name="rules[]" value="timeofday" {if !is_null($crit_timeofday)}checked="checked"{/if}> Time of Day:</label>
		</td>
		<td valign="top">
			<i>from</i> 
			<select name="timeofday_from">
				{section start=0 loop=24 name=hr}
				{section start=0 step=30 loop=60 name=min}
					{assign var=hr value=$smarty.section.hr.index}
					{assign var=min value=$smarty.section.min.index}
					{if 0==$hr}{assign var=hr value=12}{/if}
					{if $hr>12}{math assign=hr equation="x-12" x=$hr}{/if}
					{assign var=val value=$smarty.section.hr.index|cat:':'|cat:$smarty.section.min.index}
					<option value="{$val}" {if $crit_timeofday.from==$val}selected="selected"{/if}>{$hr|string_format:"%d"}:{$min|string_format:"%02d"} {if $smarty.section.hr.index<12}AM{else}PM{/if}</option>
				{/section}
				{/section}
			</select>
			 <i>to</i> 
			<select name="timeofday_to">
				{section start=0 loop=24 name=hr}
				{section start=0 step=30 loop=60 name=min}
					{assign var=hr value=$smarty.section.hr.index}
					{assign var=min value=$smarty.section.min.index}
					{if 0==$hr}{assign var=hr value=12}{/if}
					{if $hr>12}{math assign=hr equation="x-12" x=$hr}{/if}
					{assign var=val value=$smarty.section.hr.index|cat:':'|cat:$smarty.section.min.index}
					<option value="{$val}" {if $crit_timeofday.to==$val}selected="selected"{/if}>{$hr|string_format:"%d"}:{$min|string_format:"%02d"} {if $smarty.section.hr.index<12}AM{else}PM{/if}</option>
				{/section}
				{/section}
			</select>
		</td>
	</tr>
</table>

{* Alert *}
{assign var=expanded value=false}
{if isset($alert->criteria.alert_last_ran)}
	{assign var=expanded value=true}
{/if}
<label><input type="checkbox" {if $expanded}checked="checked"{/if} onclick="toggleDiv('divBlockAlert',(this.checked?'block':'none'));if(!this.checked)checkAll('divBlockAlert',false);"> <b>Alert</b></label><br>
<table width="500" style="margin-left:10px;display:{if $expanded}block{else}none{/if};" id="divBlockAlert">
	<tr>
		<td valign="top">
			{assign var=crit_alert_last_ran value=$alert->criteria.alert_last_ran}
			<label><input type="checkbox" id="chkRuleAlertLastRan" name="rules[]" value="alert_last_ran" {if !is_null($crit_alert_last_ran)}checked="checked"{/if}> Last Ran Date:</label>
		</td>
		<td valign="top">
			<i>between:</i><br>
			<input type="text" name="value_alert_last_ran_from" size="20" value="{$crit_alert_last_ran.from|escape}" onchange="document.getElementById('chkRuleAlertLastRan').checked=((0==this.value.length)?false:true);" style="width:95%;"><br>
			<i>and:</i><br>
			<input type="text" name="value_alert_last_ran_to" size="20" value="{$crit_alert_last_ran.to|escape}" onchange="document.getElementById('chkRuleAlertLastRan').checked=((0==this.value.length)?false:true);" style="width:95%;"><br>
			<i>(+2 hours, now, next Friday 5pm, 2pm, Jan 25)</i>
		</td>
	</tr>
</table>

{* Sensor *}
{assign var=expanded value=false}
{if isset($alert->criteria.sensor_type) || isset($alert->criteria.sensor_name) || isset($alert->criteria.sensor_fail_count)}
	{assign var=expanded value=true}
{/if}
<label><input type="checkbox" {if $expanded}checked="checked"{/if} onclick="toggleDiv('divBlockSensor',(this.checked?'block':'none'));if(!this.checked)checkAll('divBlockSensor',false);"> <b>Sensor</b></label><br>
<table width="500" style="margin-left:10px;display:{if $expanded}block{else}none{/if};" id="divBlockSensor">
	<tr>
		<td valign="top">
			{assign var=crit_sensor_name value=$alert->criteria.sensor_name}
			<label><input type="checkbox" id="chkRuleSensorName" name="rules[]" value="sensor_name" {if !is_null($crit_sensor_name)}checked="checked"{/if}> Name:</label>
		</td>
		<td valign="top">
			<input type="text" name="value_sensor_name" size="45" value="{$crit_sensor_name.value|escape}" onchange="document.getElementById('chkRuleSensorName').checked=((0==this.value.length)?false:true);" style="width:95%;">
		</td>
	</tr>
	<tr>
		<td valign="top" colspan="2">
			{assign var=crit_sensor_types value=$alert->criteria.sensor_type}
			<label><input type="checkbox" id="chkRuleSensorTypes" name="rules[]" value="sensor_type" onclick="toggleDiv('divRuleSensorTypes',(this.checked?'block':'none'));" {if !is_null($crit_sensor_types)}checked="checked"{/if}> Type:</label>
			
			<div id="divRuleSensorTypes" style="margin-left:20px;display:{if !is_null($crit_sensor_types)}block{else}none{/if};">
				{foreach from=$sensor_type_mfts item=sensor_type_mft key=sensor_type_mft_id}
					<label><input type="checkbox" name="value_sensor_types[]" value="{$sensor_type_mft->id}" {if isset($crit_sensor_types.$sensor_type_mft_id)}checked="checked"{/if}>{$sensor_type_mft->name}</label><br>
				{/foreach}
			</div>
		</td>
	</tr>
	<tr>
		<td valign="top">
			{assign var=crit_sensor_fail_count value=$alert->criteria.sensor_fail_count}
			<label><input type="checkbox" id="chkRuleSensorFailCount" name="rules[]" value="sensor_fail_count" {if !is_null($crit_sensor_fail_count)}checked="checked"{/if}> Fail Count:</label>
		</td>
		<td valign="top">
			<select name="oper_sensor_fail_count">
				<option value="=" {if '='==$crit_sensor_fail_count.oper}selected="selected"{/if}>{'search.oper.equals'|devblocks_translate}</option>
				<option value="!=" {if '!='==$crit_sensor_fail_count.oper}selected="selected"{/if}>{'search.oper.equals.not'|devblocks_translate}</option>
				<option value="&gt;" {if '>'==$crit_sensor_fail_count.oper}selected="selected"{/if}>&gt;</option>
				<option value="&lt;" {if '<'==$crit_sensor_fail_count.oper}selected="selected"{/if}>&lt;</option>
			</select>
			<input type="text" name="value_sensor_fail_count" size="8" value="{$crit_sensor_fail_count.value|escape}" onchange="document.getElementById('chkRuleSensorFailCount').checked=((0==this.value.length)?false:true);">
		</td>
	</tr>
</table>

{* Get Sensor Fields *}
{include file="file:$core_tpl/internal/custom_fields/filters/peek_get_custom_fields.tpl" fields=$sensor_fields filter=$alert divName="divGetSensorFields" label="Sensor custom fields"}

{if is_array($alert_criteria_exts) && !empty($alert_criteria_exts)}
{foreach from=$alert_criteria_exts item=alert_criteria_ext key=extid}
{assign var=ext_crit value=$alert->criteria.$extid}
<label><input type="checkbox" name="rules[]" value="{$extid}" {if !is_null($ext_crit)}checked="checked"{/if} onclick="toggleDiv('crit_{$extid}',(this.checked?'block':'none'));"> <b>{$alert_criteria_ext->manifest->name}</b></label><br>
<blockquote style="margin:0px 0px 5px 10px;display:{if !is_null($ext_crit)}block{else}none{/if};" id="crit_{$extid}">
	{if method_exists($alert_criteria_ext,'renderConfig')}{$alert_criteria_ext->renderConfig($alert)}{/if}
</blockquote>
{/foreach}
{/if}

<br>
<h2>Then perform these actions:</h2>

{* Set Sensor Fields *}
{include file="file:$core_tpl/internal/custom_fields/filters/peek_set_custom_fields.tpl" fields=$sensor_fields filter=$alert divName="divSetSensorFields" label="Set sensor custom fields"}

<label><input type="checkbox" name="do[]" value="notify" {if !is_null($alert->actions.notify)}checked="checked"{/if}> <b>Send a worker notification</b></label><br>
{*
<blockquote style="margin-top:0px;" id="div_do_notity">
</blockquote>
*}

{if is_array($alert_action_exts) && !empty($alert_action_exts)}
{foreach from=$alert_action_exts item=alert_action_ext key=extid}
{assign var=ext_act value=$alert->actions.$extid}
<label><input type="checkbox" name="do[]" value="{$extid}" {if !is_null($ext_act)}checked="checked"{/if} onclick="toggleDiv('do_{$extid}',(this.checked?'block':'none'));"> <b>{$alert_action_ext->manifest->name}</b></label><br>
<blockquote style="margin:0px 0px 5px 20px;display:{if !is_null($ext_act)}block{else}none{/if};" id="do_{$extid}">
	{if method_exists($alert_action_ext,'renderConfig')}{$alert_action_ext->renderConfig($alert)}{/if}
</blockquote>
{/foreach}
{/if}

<br>

<button type="button" onclick="genericPanel.dialog('close');genericAjaxPost('frmAlertFilter', 'view{$view_id}', '');"><img src="{devblocks_url}c=resource&p=portsensor.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button>
{if !empty($alert->id)}{* [TODO] ACL *}<button type="button" onclick="if(confirm('Are you sure you want to delete this alert?')){literal}{{/literal}this.form.do_delete.value='1';genericPanel.dialog('close');genericAjaxPost('frmAlertFilter', 'view{$view_id}', '');{literal}}{/literal}"><img src="{devblocks_url}c=resource&p=portsensor.core&f=images/delete2.gif{/devblocks_url}" align="top"> {$translate->_('common.delete')|capitalize}</button>{/if}
<button type="button" onclick="genericPanel.dialog('close');"><img src="{devblocks_url}c=resource&p=portsensor.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
</form>
<br>

<script type="text/javascript" language="JavaScript1.2">
	$(genericPanel).one('dialogopen',function(event,ui) {
		genericPanel.dialog('option','title','Alert'); 
	} );
</script>

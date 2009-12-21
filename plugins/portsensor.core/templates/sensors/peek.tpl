<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td width="100%">
			{if empty($sensor->id)}
			<h1>Add Sensor</h1>
			{else}
			<h1>Sensor: {$sensor->name|escape}</h1>
			{/if}
		</td>
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formSensorPeek" name="formSensorPeek" onsubmit="return false;">
<input type="hidden" name="c" value="sensors">
<input type="hidden" name="a" value="saveSensorPeek">
<input type="hidden" name="id" value="{$sensor->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">

<div style="height:350px;overflow:auto;margin:2px;padding:3px;">

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap" align="right"><b>{$translate->_('sensor.name')|capitalize}:</b> </td>
		<td width="100%"><input type="text" name="name" value="{$sensor->name|escape}" style="width:98%;"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top"><b>{$translate->_('sensor.extension_id')|capitalize}:</b> </td>
		<td width="100%">
			<select name="extension_id"> {* onchange="toggleDiv('divExtensionManual',(selectValue(this))==''?'block':'none');" *}
				<option value="" {if empty($sensor->extension_id)}selected="selected"{/if}}>- Manual (External/API) -</option>
				{foreach from=$sensor_types item=ext}
				<option value="{$ext->id}" {if 0==strcasecmp($ext->id,$sensor->extension_id)}selected="selected"{/if}>
					{$ext->name}
				</option>
				{/foreach}
			</select>
			{*
			<blockquote id="divExtensionManual" style="margin:5px;background-color:rgb(255,255,255);padding:5px;border:1px dotted rgb(120,120,120);display:{if empty($sensor->id)||$sensor->extension_id==''}block{else}none{/if};">
				<b>{$translate->_('sensor.metric_type')|capitalize}:</b><br>
				<select name="metric_type">
					<option value="T" {if 'T'==$sensor->metric_type}selected="selected"{/if}}>Text</option>
					<option value="D" {if 'D'==$sensor->metric_type}selected="selected"{/if}}>Decimal</option>
					<option value="N" {if 'N'==$sensor->metric_type}selected="selected"{/if}}>Number</option>
					<option value="U" {if 'U'==$sensor->metric_type}selected="selected"{/if}}>Up/Down</option>
				</select><br>
			</blockquote>
			*}
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right">{$translate->_('sensor.is_disabled')|capitalize}: </td>
		<td width="100%">
			<select name="is_disabled">
				<option value="0" {if !$sensor->is_disabled}selected{/if}>{$translate->_('common.no')|capitalize}</option>
				<option value="1" {if $sensor->is_disabled}selected{/if}>{$translate->_('common.yes')|capitalize}</option>
			</select>
		</td>
	</tr>
</table>

{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=false}
<br>

</div>

{* [TODO] ACL *}
{if 1 || $active_worker->is_superuser}
	<button type="button" onclick="genericPanel.hide();genericAjaxPost('formSensorPeek', 'view{$view_id}', '');"><img src="{devblocks_url}c=resource&p=portsensor.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button>
	{if !empty($sensor->id)}{* [TODO] ACL *}<button type="button" onclick="if(confirm('Are you sure you want to delete this sensor?')){literal}{{/literal}this.form.do_delete.value='1';genericPanel.hide();genericAjaxPost('formSensorPeek', 'view{$view_id}', '');{literal}}{/literal}"><img src="{devblocks_url}c=resource&p=portsensor.core&f=images/delete2.gif{/devblocks_url}" align="top"> {$translate->_('common.delete')|capitalize}</button>{/if}
{else}
	<div class="error">{$translate->_('error.core.no_acl.edit')}</div>	
{/if}
<button type="button" onclick="genericPanel.hide();genericAjaxPostAfterSubmitEvent.unsubscribeAll();"><img src="{devblocks_url}c=resource&p=portsensor.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>

<br>
</form>

<form action="{devblocks_url}{/devblocks_url}" method="post" style="padding-bottom:5px;">
	<input type="hidden" name="c" value="sensors">
	<input type="hidden" name="a" value="">
	{*if $active_worker->hasPriv('crm.opp.actions.create')*}<button type="button" onclick="genericAjaxPanel('c=sensors&a=showSensorPeek&id=0&view_id={$view->id}',this,false,'500px');"><img src="{devblocks_url}c=resource&p=portsensor.core&f=images/led_green.png{/devblocks_url}" align="top"> {$translate->_('sensors.add')}</button>{*/if*}
</form>

<table cellpadding="0" cellspacing="0" width="100%">
<tr>
	<td width="0%" nowrap="nowrap" valign="top">
		<div style="width:220px;">
			{include file="file:$core_tpl/internal/views/criteria_list.tpl" divName="sensorSearchFilters"}
			<div id="sensorSearchFilters" style="visibility:visible;"></div>
		</div>
	</td>
	
	<td nowrap="nowrap" width="0%"><img src="{devblocks_url}c=resource&p=portsensor.core&f=images/spacer.gif{/devblocks_url}" width="5" height="1"></td>
	
	<td width="100%" valign="top">
		<div id="view{$view->id}">{$view->render()}</div>
	</td>
	
</tr>
</table>

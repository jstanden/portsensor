<div id="headerSubMenu">
	<div style="padding-bottom:5px;"></div>
</div> 

{*
<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td width="1%" nowrap="nowrap" valign="top" style="padding-right:5px;">
		<form action="{devblocks_url}{/devblocks_url}" method="post" style="padding-bottom:5px;">
			<input type="hidden" name="c" value="sensors">
			<input type="hidden" name="a" value="">
			{if $active_worker->hasPriv('crm.opp.actions.create')}<button type="button" onclick="genericAjaxPanel('c=sensors&a=showSensorPeek&id=0&view_id={$view->id}',this,false,'500px');"><img src="{devblocks_url}c=resource&p=portsensor.core&f=images/led_green.png{/devblocks_url}" align="top"> {$translate->_('sensors.add')}</button>{/if}
		</form>
	</td>
	<td width="98%" valign="middle"></td>
	<td width="1%" nowrap="nowrap" valign="middle" align="right">
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="sensors">
		<input type="hidden" name="a" value="doQuickSearch">
		<b>{$translate->_('common.quick_search')}</b> <select name="type">
			<option value="email"{if $quick_search_type eq 'email'}selected{/if}>{$translate->_('crm.opportunity.email_address')|capitalize}</option>
			<option value="org"{if $quick_search_type eq 'org'}selected{/if}>{$translate->_('crm.opportunity.org_name')|capitalize}</option>
			<option value="title"{if $quick_search_type eq 'title'}selected{/if}>{$translate->_('crm.opportunity.name')|capitalize}</option>
		</select><input type="text" name="query" size="16"><button type="submit">{$translate->_('common.search_go')|lower}</button>
		</form>
	</td>
</tr>
</table>
*}

<div id="sensorTabs"></div> 
<br>

{include file="file:$core_tpl/whos_online.tpl"}

<script type="text/javascript">
{literal}
var tabView = new YAHOO.widget.TabView();
{/literal}

{literal}
tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{$translate->_('sensors.tab.all_sensors')|escape:'quotes'}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=sensors&a=showTabAllSensors&request={$request_path|escape:'url'}{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if empty($selected_tab) || 'all'==$selected_tab}true{else}false{/if}{literal}
}));
{/literal}

{foreach from=$tab_manifests item=tab_manifest name=sensors_tabs}
{if !isset($tab_manifest->params.acl) || $worker->hasPriv($tab_manifest->params.acl)}
{literal}tabView.addTab(new YAHOO.widget.Tab({{/literal}
    label: '{$tab_manifest->params.title|devblocks_translate|escape:'quotes'}',
    dataSrc: '{devblocks_url}ajax.php?c=sensors&a=showTab&ext_id={$tab_manifest->id}&request={$request_path|escape:'url'}{/devblocks_url}',
    {if $tab_selected==$tab_manifest->params.uri || (empty($tab_selected) && $smarty.foreach.sensors_tabs.first)}active: true,{/if}
    cacheData: false
{literal}}));{/literal}
{/if}
{/foreach}

tabView.appendTo('sensorTabs');
</script>

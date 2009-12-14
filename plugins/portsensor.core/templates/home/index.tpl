<div id="headerSubMenu">
	<div style="padding-bottom:5px;"></div>
</div> 

<div id="homeTabs"></div> 
<br>

{include file="file:$core_tpl/whos_online.tpl"}

<script type="text/javascript">
{literal}
var tabView = new YAHOO.widget.TabView();
{/literal}

{literal}
tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{$translate->_('home.tab.notifications')|escape:'quotes'}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=home&a=showTabNotifications&request={$request_path|escape:'url'}{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if empty($selected_tab) || 'notifications'==$selected_tab}true{else}false{/if}{literal}
}));
{/literal}

{foreach from=$tab_manifests item=tab_manifest name=home_tabs}
{if !isset($tab_manifest->params.acl) || $worker->hasPriv($tab_manifest->params.acl)}
{literal}tabView.addTab(new YAHOO.widget.Tab({{/literal}
    label: '{$tab_manifest->params.title|devblocks_translate|escape:'quotes'}',
    dataSrc: '{devblocks_url}ajax.php?c=home&a=showTab&ext_id={$tab_manifest->id}&request={$request_path|escape:'url'}{/devblocks_url}',
    {if $tab_selected==$tab_manifest->params.uri || (empty($tab_selected) && $smarty.foreach.home_tabs.first)}active: true,{/if}
    cacheData: false
{literal}}));{/literal}
{/if}
{/foreach}

tabView.appendTo('homeTabs');
</script>

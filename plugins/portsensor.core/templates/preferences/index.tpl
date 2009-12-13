<div id="headerSubMenu">
	<div style="padding-bottom:5px;"></div>
</div> 

<div id="prefsTabs"></div> 
<br>

<script type="text/javascript">
{literal}
var tabView = new YAHOO.widget.TabView();
{/literal}

{literal}
tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{$translate->_('preferences.tab.general')|escape:'quotes'}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=preferences&a=showGeneralTab&request={$request_path|escape:'url'}{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if empty($selected_tab) || 'general'==$selected_tab}true{else}false{/if}{literal}
}));
{/literal}

{foreach from=$tab_manifests item=tab_manifest name=prefs_tabs}
{if !isset($tab_manifest->params.acl) || $worker->hasPriv($tab_manifest->params.acl)}
{literal}tabView.addTab(new YAHOO.widget.Tab({{/literal}
    label: '{$tab_manifest->params.title|devblocks_translate|escape:'quotes'}',
    dataSrc: '{devblocks_url}ajax.php?c=preferences&a=showTab&ext_id={$tab_manifest->id}&request={$request_path|escape:'url'}{/devblocks_url}',
    {if $tab_selected==$tab_manifest->params.uri || (empty($tab_selected) && $smarty.foreach.prefs_tabs.first)}active: true,{/if}
    cacheData: false
{literal}}));{/literal}
{/if}
{/foreach}

tabView.appendTo('prefsTabs');
</script>

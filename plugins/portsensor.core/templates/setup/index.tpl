<div id="headerSubMenu">
	<div style="padding-bottom:5px;"></div>
</div> 

{if $install_dir_warning}
<div class="error">
	Warning: The 'install' directory still exists.  This is a potential security risk.  Please delete it.
</div>
{/if}

<div id="setupTabs"></div> 
<br>

<script type="text/javascript">
{literal}
var tabView = new YAHOO.widget.TabView();
{/literal}

{literal}
tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{$translate->_('setup.tab.settings')|escape:'quotes'}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=setup&a=showTabSettings&request={$request_path|escape:'url'}{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if empty($tab_selected) || 'settings'==$tab_selected}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{$translate->_('setup.tab.plugins')|escape:'quotes'}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=setup&a=showTabPlugins&request={$request_path|escape:'url'}{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if 'plugins'==$tab_selected}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{$translate->_('setup.tab.mail_setup')|escape:'quotes'}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=setup&a=showTabMailSetup&request={$request_path|escape:'url'}{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if 'mail'==$tab_selected}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{$translate->_('setup.tab.scheduler')|escape:'quotes'}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=setup&a=showTabScheduler&request={$request_path|escape:'url'}{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if 'scheduler'==$tab_selected}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{$translate->_('setup.tab.workers')|escape:'quotes'}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=setup&a=showTabWorkers&request={$request_path|escape:'url'}{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if 'workers'==$tab_selected}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{$translate->_('setup.tab.acl')|escape:'quotes'}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=setup&a=showTabACL&request={$request_path|escape:'url'}{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if 'acl'==$tab_selected}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{$translate->_('setup.tab.fields')|escape:'quotes'}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=setup&a=showTabFields&request={$request_path|escape:'url'}{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if 'fields'==$tab_selected}true{else}false{/if}{literal}
}));
{/literal}

{foreach from=$tab_manifests item=tab_manifest name=setup_tabs}
{if !isset($tab_manifest->params.acl) || $worker->hasPriv($tab_manifest->params.acl)}
{literal}tabView.addTab(new YAHOO.widget.Tab({{/literal}
    label: '<i>{$tab_manifest->params.title|devblocks_translate|escape:'quotes'}</i>',
    dataSrc: '{devblocks_url}ajax.php?c=setup&a=showTab&ext_id={$tab_manifest->id}&request={$request_path|escape:'url'}{/devblocks_url}',
    {if $tab_selected==$tab_manifest->params.uri}active: true,{/if}
    cacheData: false
{literal}}));{/literal}
{/if}
{/foreach}

tabView.appendTo('setupTabs');
</script>

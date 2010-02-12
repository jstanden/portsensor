<div id="headerSubMenu">
	<div style="padding-bottom:5px;"></div>
</div> 

{if $install_dir_warning}
<div class="ui-widget">
	<div class="ui-state-error ui-corner-all" style="padding: 0 .7em; margin: 0.2em; "> 
		<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span> 
		<strong>Warning:</strong> The 'install' directory still exists.  This is a potential security risk.  Please delete it.</p>
	</div>
</div>
{/if}

<div id="setupTabs">
	<ul>
		<li><a href="{devblocks_url}ajax.php?c=setup&a=showTabSettings&request={$request_path|escape:'url'}{/devblocks_url}">{$translate->_('setup.tab.settings')|escape:'quotes'}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=setup&a=showTabPlugins&request={$request_path|escape:'url'}{/devblocks_url}">{$translate->_('setup.tab.plugins')|escape:'quotes'}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=setup&a=showTabMailSetup&request={$request_path|escape:'url'}{/devblocks_url}">{$translate->_('setup.tab.mail_setup')|escape:'quotes'}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=setup&a=showTabScheduler&request={$request_path|escape:'url'}{/devblocks_url}">{$translate->_('setup.tab.scheduler')|escape:'quotes'}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=setup&a=showTabWorkers&request={$request_path|escape:'url'}{/devblocks_url}">{$translate->_('setup.tab.workers')|escape:'quotes'}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=setup&a=showTabACL&request={$request_path|escape:'url'}{/devblocks_url}">{$translate->_('setup.tab.acl')|escape:'quotes'}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=setup&a=showTabFields&request={$request_path|escape:'url'}{/devblocks_url}">{$translate->_('setup.tab.fields')|escape:'quotes'}</a></li>

		{$tabs = [settings,plugins,mail,scheduler,workers,acl,fields]}

		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=setup&a=showTab&ext_id={$tab_manifest->id}&request={$request_path|escape:'url'}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate|escape:'quotes'}</i></a></li>
		{/foreach}
	</ul>
</div> 
<br>

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$tab_selected}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#setupTabs").tabs( { selected:{$tab_selected_idx} } );
	});
</script>

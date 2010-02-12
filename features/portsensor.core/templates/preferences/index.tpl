<div id="headerSubMenu">
	<div style="padding-bottom:5px;"></div>
</div> 

<h1>{'core.menu.preferences'|devblocks_translate|capitalize}</h1>

{if is_array($pref_errors) && !empty($pref_errors)}
	<div class="error">
		<ul style="margin:2px;">
		{foreach from=$pref_errors item=error}
			<li>{$error}</li>
		{/foreach}
		</ul>
	</div>
{elseif is_array($pref_success) && !empty($pref_success)}
	<div class="success">
		<ul style="margin:2px;">
		{foreach from=$pref_success item=success}
			<li>{$success}</li>
		{/foreach}
		</ul>
	</div>
{else}
	<br>
{/if}

<div id="prefsTabs">
	<ul>
		<li><a href="{devblocks_url}ajax.php?c=preferences&a=showTabGeneral&request={$request_path|escape:'url'}{/devblocks_url}">{$translate->_('preferences.tab.general')|escape:'quotes'}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=preferences&a=showTabAlerts&request={$request_path|escape:'url'}{/devblocks_url}">{$translate->_('preferences.tab.alerts')|escape:'quotes'}</a></li>

		{$tabs = [general,alerts]}

		{foreach from=$tab_manifests item=tab_manifest}
			{if !isset($tab_manifest->params.acl) || $worker->hasPriv($tab_manifest->params.acl)}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=preferences&a=showTab&ext_id={$tab_manifest->id}&request={$request_path|escape:'url'}{/devblocks_url}">{$tab_manifest->params.title|devblocks_translate|escape:'quotes'}</a></li>
			{/if}
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
		var tabs = $("#prefsTabs").tabs( { selected:{$tab_selected_idx} } );
	});
</script>
<div id="headerSubMenu">
	<div style="padding-bottom:5px;"></div>
</div> 

<form action="{devblocks_url}{/devblocks_url}" method="POST" style="margin-bottom:5px;">
{if 1||$active_worker->hasPriv('core.home.workspaces')}<button type="button" onclick="genericAjaxPanel('c=home&a=showAddWorkspacePanel',null,false,'550');"><img src="{devblocks_url}c=resource&p=portsensor.core&f=images/document_plain_new.png{/devblocks_url}" align="top"> {$translate->_('home.workspaces.worklist.button.add')|capitalize}</button>{/if}
{if 1||$active_worker->hasPriv('core.home.auto_refresh')}<button type="button" onclick="autoRefreshTimer.start('{devblocks_url full=true}c=home{/devblocks_url}',this.form.reloadSecs.value);"><img src="{devblocks_url}c=resource&p=portsensor.core&f=images/refresh.gif{/devblocks_url}" align="top"> {'common.refresh.auto'|devblocks_translate|capitalize}</button><!-- 
--><select name="reloadSecs">
	<option value="600">{'common.time.mins.num'|devblocks_translate:'10'}</option>
	<option value="300" selected="selected">{'common.time.mins.num'|devblocks_translate:'5'}</option>
	<option value="240">{'common.time.mins.num'|devblocks_translate:'4'}</option>
	<option value="180">{'common.time.mins.num'|devblocks_translate:'3'}</option>
	<option value="120">{'common.time.mins.num'|devblocks_translate:'2'}</option>
	<option value="60">{'common.time.mins.num'|devblocks_translate:'1'}</option>
	<option value="30">{'common.time.secs.num'|devblocks_translate:'30'}</option>
</select>{/if}
</form>

<div id="homeTabs">
	<ul>
		<li><a href="{devblocks_url}ajax.php?c=home&a=showTabNotifications&request={$request_path|escape:'url'}{/devblocks_url}">{$translate->_('home.tab.notifications')|escape:'quotes'}</a></li>

		{$tabs = [notifications]}

		{foreach from=$tab_manifests item=tab_manifest}
			{if !isset($tab_manifest->params.acl) || $worker->hasPriv($tab_manifest->params.acl)}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=home&a=showTab&ext_id={$tab_manifest->id}&request={$request_path|escape:'url'}{/devblocks_url}">{$tab_manifest->params.title|devblocks_translate|escape:'quotes'}</a></li>
			{/if}
		{/foreach}
		
		{if 1||$active_worker->hasPriv('core.home.workspaces')}
		{foreach from=$workspaces item=workspace}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=home&a=showWorkspaceTab&workspace={$workspace|escape:'url'}{/devblocks_url}"><i>{$workspace|escape}</i></a></li>
		{/foreach}
		{/if}
	</ul>
</div> 
<br>

{include file="file:$core_tpl/whos_online.tpl"}

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$tab_selected}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#homeTabs").tabs( { selected:{$tab_selected_idx} } );
	});
</script>


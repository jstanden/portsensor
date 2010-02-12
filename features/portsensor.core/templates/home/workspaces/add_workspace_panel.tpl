<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="home">
<input type="hidden" name="a" value="doAddWorkspace">
<H1>{$translate->_('home.workspaces.worklist.button.add')|capitalize}</H1>
<br>

<b>{'home.workspaces.worklist.name'|devblocks_translate|capitalize}:</b><br>
<input type="text" name="name" value="" size="35" style="width:100%;"><br>
<br>

<b>{'home.workspaces.worklist.type'|devblocks_translate|capitalize}:</b><br>
<select name="source">
	{foreach from=$sources item=mft key=mft_id}
	<option value="{$mft_id}">{$mft->name}</option>
	{/foreach}
</select><br>
<br>

<b>{'home.workspaces.worklist.add.to_workspace'|devblocks_translate}:</b><br>
{if !empty($workspaces)}
{'home.workspaces.worklist.add.existing'|devblocks_translate|capitalize}: <select name="workspace">
	{foreach from=$workspaces item=workspace}
	<option value="{$workspace|escape}">{$workspace}</option>
	{/foreach}
</select><br>
-{'common.or'|devblocks_translate|lower}-<br>
{/if}
{'home.workspaces.worklist.add.new'|devblocks_translate|capitalize}: <input type="text" name="new_workspace" size="32" maxlength="32" value=""><br>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=portsensor.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button>
<button type="button" onclick="genericPanel.dialog('close');"><img src="{devblocks_url}c=resource&p=portsensor.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
</form>

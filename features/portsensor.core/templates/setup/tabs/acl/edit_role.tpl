<input type="hidden" name="c" value="setup">
<input type="hidden" name="a" value="saveRole">
<input type="hidden" name="id" value="{if !empty($role->id)}{$role->id}{else}0{/if}">
<input type="hidden" name="do_delete" value="0">
<div class="block">
<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<tr>
		<td colspan="2">
			{if empty($role->id)}
			<h2>Add Role</h2>
			{else}
			<h2>Modify '{$role->name}'</h2>
			{/if}
		</td>
	</tr>

	<tr>
		<td colspan="2" style="padding-top:5px;">
			<h3>Role Name</h3>
			<input type="text" name="name" value="{$role->name|escape}" size="45" style="width:98%;">
		</td>
	</tr>
	
	{if !empty($workers)}
	<tr>
		<td colspan="2" style="padding-top:5px;">
			<h3>Workers</h3>
			
			<div style="margin-left:10px;">
			{foreach from=$workers item=worker key=worker_id}
				<label><input type="checkbox" name="worker_ids[]" value="{$worker_id}" {if isset($role_workers.$worker_id)}checked="checked"{/if}> {$worker->getName()}{if !empty($worker->title)} (<span style="color:rgb(0,120,0);">{$worker->title}</span>){/if}</label><br>
			{/foreach}
			</div>
		</td>
	</tr>
	{/if}

	<tr>
		<td colspan="2" style="padding-top:5px;">
			<h3>Permissions</h3>
		</td>
	</tr>
	
	<tr>
		<td width="100%" valign="top" colspan="2">
			{foreach from=$plugins item=plugin key=plugin_id}
				{if $plugin->enabled}
					{assign var=plugin_priv value="plugin."|cat:$plugin_id}
					<div style="margin-left:10px;background-color:rgb(255,255,221);border:2px solid rgb(255,215,0);padding:2px;margin-bottom:10px;">
					<label>
					{if $plugin->id=="portsensor.core"}
						<input type="hidden" name="acl_privs[]" value="plugin.portsensor.core">
					{else}
						<input type="checkbox" name="acl_privs[]" value="{$plugin_priv|escape}" {if isset($role_privs.$plugin_priv)}checked="checked"{/if} onchange="toggleDiv('privs{$plugin_id}',(this.checked)?'block':'none');">
					{/if}
					<b>{$plugin->name}</b></label><br>
						<div id="privs{$plugin_id}" style="padding-left:10px;margin-bottom:5px;display:{if $plugin->id=="portsensor.core" || isset($role_privs.$plugin_priv)}block{else}none{/if}">
						<a href="javascript:;" style="font-size:90%;" onclick="checkAll('privs{$plugin_id}');">{$translate->_('check all')|lower}</a><br>
						{foreach from=$acl item=priv key=priv_id}
							{if $priv->plugin_id==$plugin_id}
							<label style=""><input type="checkbox" name="acl_privs[]" value="{$priv_id}" {if isset($role_privs.$priv_id)}checked{/if}> {$priv->label|devblocks_translate}</label><br>
							{/if}
						{/foreach}
						</div>
					</div>
				{/if}
			{/foreach}
		</td>
	</tr>
	
	<tr>
		<td colspan="2">
			{if isset($license.serial) && !isset($license.a)}
				<button type="submit"><img src="{devblocks_url}c=resource&p=portsensor.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
				{if $active_worker->is_superuser}<button type="button" onclick="if(confirm('Are you sure you want to delete this role?')){literal}{{/literal}this.form.do_delete.value='1';this.form.submit();{literal}}{/literal}"><img src="{devblocks_url}c=resource&p=portsensor.core&f=images/delete2.gif{/devblocks_url}" align="top"> {$translate->_('common.delete')|capitalize}</button>{/if}
			{/if}
		</td>
	</tr>
</table>
</div>
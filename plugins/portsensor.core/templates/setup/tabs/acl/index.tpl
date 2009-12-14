<form>
	<b>Worker Permissions:</b> 
	<label><input type="radio" name="enabled" value="1" onchange="if(this.checked)toggleDiv('configACL','block');genericAjaxGet('','c=setup&a=toggleACL&enabled=1');" {if $acl_enabled}checked="checked"{/if}> Enabled</label>
	<label><input type="radio" name="enabled" value="0" onchange="if(this.checked)toggleDiv('configACL','none');genericAjaxGet('','c=setup&a=toggleACL&enabled=0');" {if !$acl_enabled}checked="checked"{/if}> Disabled</label>
</form>

<table cellpadding="0" cellspacing="5" border="0" width="100%" id="configACL" style="display:{if !$acl_enabled}none{else}block{/if};">
	<tr>
		{if 1 || isset($license.serial) && !isset($license.a)}
		<td width="0%" nowrap="nowrap" valign="top">
			<div class="block">
			<table cellpadding="2" cellspacing="0" border="0">
				<tr>
					<td><h2>Roles</h2></td>
				</tr>
				<tr>
					<td>
						[ <a href="javascript:;" onclick="genericAjaxGet('configRole','c=setup&a=getRole&id=0');">add new role</a> ]
					</td>
				</tr>
				<tr>
					<td>
						<div style="margin:0px;padding:3px;width:200px;overflow:auto;">
						{if !empty($roles)}
							{foreach from=$roles item=role key=role_id}
							&#187; <a href="javascript:;" onclick="genericAjaxGet('configRole','c=setup&a=getRole&id={$role_id}');">{$role->name}</a><br>
							{/foreach}
						{/if}
						</div>
					</td>
				</tr>
			</table>
			</div>
		</td>
		{/if}
		
		<td width="100%" valign="top">
			<form action="{devblocks_url}{/devblocks_url}" method="post" id="configRole">
				{include file="$core_tpl/setup/tabs/acl/edit_role.tpl" role=null}
			</form>
		</td>
		
	</tr>
</table>



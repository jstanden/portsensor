<table border="0" cellspacing="0" cellpadding="2">
	<tr>
		<td valign="top">To:</td>
		<td valign="top">
			<input type="text" name="alert_action_sms_to" value="{$params.to|escape}" size="45"><br>
			<i>(including country code and area code, e.g.: 15551234567)</i><br>
			<i>(separate multiple phone numbers with commas)</i><br>
		</td>
	</tr>
	<tr>
		<td valign="top">Message:</td>
		<td valign="top">
			<textarea name="alert_action_sms_tpl" rows="10" cols="45" style="width:98%;">{$params.template_msg|escape}</textarea><br>
			<i>(max length per message is 160 characters)</i><br>
			
			{foreach from=$models key=model_name item=model name=models}
				<div class="block">
				<b>{$model_name}:</b>
				{foreach from=$model key=field_name item=field_default_value name=fields}
					${$model_name}->{$field_name}{if !$smarty.foreach.fields.last}, {/if}
				{/foreach}
				</div>
			{/foreach}
		</td>
	</tr>
</table>
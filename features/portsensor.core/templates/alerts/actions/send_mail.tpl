<table border="0" cellspacing="0" cellpadding="2">
	<tr>
		<td valign="top">To:</td>
		<td valign="top">
			<input type="text" name="alert_action_mail_to" value="{$params.to|escape}" size="45"><br>
			<i>(separate multiple addresses with commas)</i><br>
		</td>
	</tr>
	<tr>
		<td valign="top">Template:</td>
		<td valign="top">
			Subject:<br>
			<input type="text" name="alert_action_mail_subject_tpl" value="{$params.template_subject|escape}" size="45" style="width:98%;"><br>
			Body:<br>
			<textarea name="alert_action_mail_body_tpl" rows="10" cols="45" style="width:98%;">{$params.template_body|escape}</textarea><br>
			
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
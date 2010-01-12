<div class="block">
	<form action="{devblocks_url}{/devblocks_url}" method="post">
	<input type="hidden" name="c" value="setup">
	<input type="hidden" name="a" value="saveTab">
	<input type="hidden" name="ext_id" value="sms.setup.tab">
	
	<h2>SMS Gateway (Clickatell.com)</h2>
	
	<b>Username:</b><br>
	<input type="text" name="clickatell_username" value="{$settings->get('portsensor.sms','clickatell_username')|escape}"><br>
	<br>
	
	<b>Password:</b><br>
	<input type="text" name="clickatell_password" value="{$settings->get('portsensor.sms','clickatell_password')|escape}"><br>
	<br>
	
	<b>API ID:</b><br>
	<input type="text" name="clickatell_api_id" value="{$settings->get('portsensor.sms','clickatell_api_id')|escape}"><br>
	<br>
	
	<button type="submit"><img src="{devblocks_url}c=resource&p=portsensor.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
	
	</form>
</div>
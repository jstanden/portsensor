<form action="{devblocks_url}{/devblocks_url}" onsubmit="pwsMatch=(this.change_pass.value==this.change_pass_verify.value);if(!pwsMatch)document.getElementById('preferences_error').innerHTML='The passwords entered do not match.  Try again.';return pwsMatch;" method="post">
<input type="hidden" name="c" value="preferences">
<input type="hidden" name="a" value="saveTabGeneral">

<div class="block">
	<h2>{$translate->_('preferences.general.account_settings')|capitalize}</h2>
	<br>
	
	<b>{$translate->_('preferences.general.password.change')|capitalize}</b><br>
	<div id="preferences_error" style="color: red; font-weight: bold;"></div>
	<table cellspacing="1" cellpadding="0" border="0">
		<tr>
			<td>{$translate->_('preferences.general.password.new')|capitalize}</td>
			<td><input type="password" name="change_pass" value=""></td>
		</tr>
		<tr>
			<td>{$translate->_('preferences.general.password.verify')|capitalize}</td>
			<td><input type="password" name="change_pass_verify"=""></td>
		</tr>
	</table>
	<br>
	
	<b>{$translate->_('preferences.general.timezone')|capitalize}</b> {if !empty($server_timezone)}({$translate->_('preferences.general.current')} {$server_timezone}){/if}<br>
	<select name="timezone">
		{foreach from=$timezones item=tz}
			<option value="{$tz}" {if $tz==$server_timezone}selected{/if}>{$tz}</option>
		{/foreach}
	</select><br>
	<br>
	
	<b>{$translate->_('preferences.general.language')|capitalize}</b> {if !empty($selected_language) && isset($langs.$selected_language)}({$translate->_('preferences.general.current')} {$langs.$selected_language}){/if}<br>
	<select name="lang_code">
		{foreach from=$langs key=lang_code item=lang_name}
			<option value="{$lang_code}" {if $lang_code==$selected_language}selected{/if}>{$lang_name}</option>
		{/foreach}
	</select>
</div>
<br>

<div class="block">
	<h2>{$translate->_('preferences.general.preferences')|capitalize}</h2>
	<br>
	
	<b>{$translate->_('preferences.general.assist')|capitalize}</b><br>
	<label><input type="checkbox" name="assist_mode" value="1" {if $assist_mode eq 1}checked{/if}> {$translate->_('common.enabled')|capitalize}</label><br>
	<br>
	
	<b>{$translate->_('preferences.general.keyboard.shortcuts')|capitalize}</b><br>
	<label><input type="checkbox" name="keyboard_shortcuts" value="1" {if $keyboard_shortcuts eq 1}checked{/if}> {$translate->_('common.enabled')|capitalize}</label><br>
</div>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=portsensor.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button>
</form>

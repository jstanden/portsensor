<div class="block">
<h1>{'header.signon'|devblocks_translate}</h1>

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="login">
<input type="hidden" name="a" value="authenticate">

<table cellpadding="0" cellspacing="2" border="0">
	<tr>
		<td align="right">
			{'common.email'|devblocks_translate|capitalize}:
		</td>
		<td>
			<input type="text" name="email" value="" size="45">
		</td>
	</tr>
	<tr>
		<td align="right">
			{'common.password'|devblocks_translate|capitalize}:
		</td>
		<td>
			<input type="password" name="password" value="" size="16">
			{* [TODO] Forgot link *}
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<button type="submit">{$translate->_('header.signon')|capitalize}</button>
		</td>
	</tr>
</table>

</form>

</div>
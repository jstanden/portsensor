<div class="block">
{if $failed===true}
	<div class="error">
		The confirmation email failed to send.  Please retry or contact an administrator.
	</div>
{/if}
<H1>Reset Password</H1>
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="login">
<input type="hidden" name="a" value="doRecoverStep1">
<b>Enter your e-mail address:</b><br>
<input type="text" name="email" size="45" value=""><br>
<br>
<button type="submit">Send Confirmation</button>
</form>
</div>
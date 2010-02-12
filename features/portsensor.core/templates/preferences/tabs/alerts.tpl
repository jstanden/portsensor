<form action="{devblocks_url}{/devblocks_url}" method="post" style="padding-bottom:5px;">
	<input type="hidden" name="c" value="preferences">
	<input type="hidden" name="a" value="">
</form>

<div id="view{$view->id}">{$view->render()}</div>
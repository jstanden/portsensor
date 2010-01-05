<form action="{devblocks_url}{/devblocks_url}" method="post" style="padding-bottom:5px;">
	<input type="hidden" name="c" value="preferences">
	<input type="hidden" name="a" value="">
	{*if $active_worker->hasPriv('crm.opp.actions.create')*}<button type="button" onclick="genericAjaxPanel('c=preferences&a=showAlertPeek&id=0&view_id={$view->id}',this,false,'550px');"><img src="{devblocks_url}c=resource&p=portsensor.core&f=images/bell.png{/devblocks_url}" align="top"> {$translate->_('preferences.tab.alerts.button.add')}</button>{*/if*}
</form>

<div id="view{$view->id}">{$view->render()}</div>
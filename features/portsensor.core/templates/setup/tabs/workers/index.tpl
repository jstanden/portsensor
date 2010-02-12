<form>
	{* [WGM]: Please respect our licensing and support the project! *}
	{if ((empty($license) || empty($license.serial)) && count($workers) >= 3) || (!empty($license.serial)&&!empty($license.users)&&count($workers)>=$license.users)}
	<div class="error">
		You have reached the number of workers permitted by your license.
		<a href="{devblocks_url}c=setup&a=settings{/devblocks_url}">[Enter License]</a>
		<a href="http://www.portsensor.com/buy" target="_blank">[Purchase License]</a>
	</div>
	{else}
	<button type="button" onclick="genericAjaxPanel('c=setup&a=showWorkerPeek&id=0&view_id={$view->id|escape:'url'}',null,false,'500');"><img src="{devblocks_url}c=resource&p=portsensor.core&f=images/businessman_add.gif{/devblocks_url}" align="top"> Add Worker</button>
	{/if}
</form>

<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td valign="top" width="0%" nowrap="nowrap">
			{include file="file:$core_tpl/internal/views/criteria_list.tpl" divName="workersCriteriaDialog"}
			<div id="workersCriteriaDialog" style="visibility:visible;"></div>
		</td>
		<td valign="top" width="0%" nowrap="nowrap" style="padding-right:5px;"></td>
		<td valign="top" width="100%">
			<div id="view{$view->id}">{$view->render()}</div>
		</td>
	</tr>
</table>

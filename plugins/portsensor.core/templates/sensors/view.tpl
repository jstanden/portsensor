{assign var=results value=$view->getData()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}
<table cellpadding="0" cellspacing="0" border="0" class="tableBlue" width="100%">
	<tr>
		<td nowrap="nowrap" class="tableThBlue">{$view->name} {if $view->id == 'search'}<a href="#{$view->id}_actions" style="color:rgb(255,255,255);font-size:11px;">{$translate->_('views.jump_to_actions')}</a>{/if}</td>
		<td nowrap="nowrap" class="tableThBlue" align="right">
			<a href="javascript:;" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');" class="tableThLink">{$translate->_('common.customize')|lower}</a>
			{*if $active_worker->hasPriv('core.home.workspaces')}<span style="font-size:12px"> | </span><a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowCopy&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');" class="tableThLink">{$translate->_('common.copy')|lower}</a>{/if*}
			<span style="font-size:12px"> | </span><a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');" class="tableThLink"><img src="{devblocks_url}c=resource&p=portsensor.core&f=images/refresh.gif{/devblocks_url}" border="0" align="absmiddle" title="{$translate->_('common.refresh')|lower}" alt="{$translate->_('common.refresh')|lower}"></a>
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Loading...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="#">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="c" value="sensors">
<input type="hidden" name="a" value="">
<table cellpadding="1" cellspacing="0" border="0" width="100%" class="tableRowBg">

	{* Column Headers *}
	<tr class="tableTh">
		<th style="text-align:center"><input type="checkbox" onclick="checkAll('view{$view->id}',this.checked);"></th>
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th nowrap="nowrap">
			{if $header=="x"}<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy=s_id');">{$translate->_('contact_org.id')|capitalize}</a>
			{else}<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy={$header}');">{$view_fields.$header->db_label}</a>
			{/if}
			
			{* add arrow if sorting by this column, finish table header tag *}
			{if $header==$view->renderSortBy}
				{if $view->renderSortAsc}
					<img src="{devblocks_url}c=resource&p=portsensor.core&f=images/sort_ascending.png{/devblocks_url}" align="absmiddle">
				{else}
					<img src="{devblocks_url}c=resource&p=portsensor.core&f=images/sort_descending.png{/devblocks_url}" align="absmiddle">
				{/if}
			{/if}
			</th>
		{/foreach}
	</tr>

	{* Column Data *}
	{foreach from=$data item=result key=idx name=results}

	{assign var=rowIdPrefix value="row_"|cat:$view->id|cat:"_"|cat:$result.s_id}
	{if $smarty.foreach.results.iteration % 2}
		{assign var=tableRowBg value="tableRowBg"}
	{else}
		{assign var=tableRowBg value="tableRowAltBg"}
	{/if}
	
		<tr class="{$tableRowBg}" id="{$rowIdPrefix}_s" onmouseover="toggleClass(this.id,'tableRowHover');toggleClass('{$rowIdPrefix}','tableRowHover');" onmouseout="toggleClass(this.id,'{$tableRowBg}');toggleClass('{$rowIdPrefix}','{$tableRowBg}');" onclick="if(getEventTarget(event)=='TD' || getEventTarget(event)=='DIV') checkAll('{$rowIdPrefix}_s');">
			<td align="center" rowspan="2"><input type="checkbox" name="row_id[]" value="{$result.s_id}"></td>
		</tr>
		<tr class="{$tableRowBg}" id="{$rowIdPrefix}" onmouseover="toggleClass(this.id,'tableRowHover');toggleClass('{$rowIdPrefix}_s','tableRowHover');" onmouseout="toggleClass(this.id,'{$tableRowBg}');toggleClass('{$rowIdPrefix}_s','{$tableRowBg}');" onclick="if(getEventTarget(event)=='TD') checkAll('{$rowIdPrefix}_s');">
		{foreach from=$view->view_columns item=column name=columns}
			{if substr($column,0,3)=="cf_"}
				{include file="file:$core_tpl/internal/custom_fields/view/cell_renderer.tpl"}
			{elseif $column=="s_id"}
			<td valign="top">{$result.s_id}&nbsp;</td>
			{elseif $column=="s_extension_id"}
			<td valign="top">
				{if ''==$result.$column}
					(External)
				{else}
					{assign var=ext_id value=$result.$column}
					{assign var=ext value=$sensor_types.$ext_id}
					{if $ext}{$ext->name}{/if}
				{/if}
			</td>
			{elseif $column=="s_name"}
			<td valign="top">
				{if $result.s_is_disabled}
					<img src="{devblocks_url}c=resource&p=portsensor.core&f=images/led_gray.png{/devblocks_url}" align="top">
				{else}
					{if 0==$result.s_status}
						<img src="{devblocks_url}c=resource&p=portsensor.core&f=images/led_green.png{/devblocks_url}" align="top">
					{elseif 1==$result.s_status}
						<img src="{devblocks_url}c=resource&p=portsensor.core&f=images/led_yellow.png{/devblocks_url}" align="top">
					{elseif 2==$result.s_status}
						<img src="{devblocks_url}c=resource&p=portsensor.core&f=images/led_red.png{/devblocks_url}" align="top">
					{elseif 3==$result.s_status}
						<img src="{devblocks_url}c=resource&p=portsensor.core&f=images/led_red.png{/devblocks_url}" align="top">
					{/if}
				{/if}
				<a href="javascript:;" onclick="genericAjaxPanel('c=sensors&a=showSensorPeek&id={$result.s_id}&view_id={$view->id}',this,false,'500px');" style="color:rgb(75,75,75);font-size:12px;font-weight:bold;">{$result.s_name}</a>
			</td>
			{elseif $column=="s_updated_date"}
			<td valign="top"><abbr title="{$result.s_updated_date|devblocks_date}">{$result.s_updated_date|devblocks_prettytime}</abbr>&nbsp;</td>
			{elseif $column=="s_is_disabled"}
			<td valign="top">{if $result.$column}<img src="{devblocks_url}c=resource&p=portsensor.core&f=images/check_gray.gif{/devblocks_url}" align="top">{/if}&nbsp;</td>
			{elseif $column=="s_status"}
			<td valign="top">
				{if $result.s_is_disabled}
					<span class="status_disabled">DISABLED</span>
				{else}
					{if 0==$result.$column}
						<span class="status_ok">OK</span>
					{elseif 1==$result.$column}
						<span class="status_warning">WARNING</span>
					{elseif 2==$result.$column}
						<span class="status_critical">CRITICAL</span>
					{elseif 3==$result.$column}
						<span class="status_critical">M.I.A.</span>
					{/if}
				{/if}
			</td>
			{elseif $column=="s_metric" || $column=="s_output"}
			<td valign="top">
				{if $result.s_is_disabled}
					<span class="status_disabled"></span>
				{else}
					{if 0==$result.s_status}
						<span class="status_ok">{$result.$column|nl2br}</span>
					{elseif 1==$result.s_status}
						<span class="status_warning">{$result.$column|nl2br}</span>
					{elseif 2==$result.s_status}
						<span class="status_critical">{$result.$column|nl2br}</span>
					{elseif 3==$result.s_status}
						<span class="status_critical">{$result.$column|nl2br}</span>
					{/if}
				{/if}
			</td>
			{else}
			<td valign="top">{$result.$column}&nbsp;</td>
			{/if}
		{/foreach}
		</tr>
	{/foreach}
	
</table>
<table cellpadding="2" cellspacing="0" border="0" width="100%" class="tableBg" id="{$view->id}_actions">
	{if $total}
	<tr>
		<td colspan="2">
			<button type="button" id="btn{$view->id}RunNow" onclick="this.form.a.value='viewRunNow';genericAjaxPost('viewForm{$view->id}','view{$view->id}','c=sensors');document.location.href='#top';"><img src="{devblocks_url}c=resource&p=portsensor.core&f=images/media_play_green.png{/devblocks_url}" align="top"> {$translate->_('sensors.button.run_now')}</button>
		</td>
	</tr>
	{/if}
	<tr>
		<td align="right" valign="top" nowrap="nowrap">
			{math assign=fromRow equation="(x*y)+1" x=$view->renderPage y=$view->renderLimit}
			{math assign=toRow equation="(x-1)+y" x=$fromRow y=$view->renderLimit}
			{math assign=nextPage equation="x+1" x=$view->renderPage}
			{math assign=prevPage equation="x-1" x=$view->renderPage}
			{math assign=lastPage equation="ceil(x/y)-1" x=$total y=$view->renderLimit}
			
			{* Sanity checks *}
			{if $toRow > $total}{assign var=toRow value=$total}{/if}
			{if $fromRow > $toRow}{assign var=fromRow value=$toRow}{/if}
			
			{if $view->renderPage > 0}
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page=0');">&lt;&lt;</a>
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$prevPage}');">&lt;{$translate->_('common.previous_short')|capitalize}</a>
			{/if}
			({'views.showing_from_to'|devblocks_translate:$fromRow:$toRow:$total})
			{if $toRow < $total}
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$nextPage}');">{$translate->_('common.next')|capitalize}&gt;</a>
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$lastPage}');">&gt;&gt;</a>
			{/if}
		</td>
	</tr>
</table>
</form>
<br>

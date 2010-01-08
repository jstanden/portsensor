<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmFindStringsEntry">
<input type="hidden" name="c" value="translators">
<input type="hidden" name="a" value="saveFindStringsPanel">

<h1>{$translate->_('common.synchronize')|capitalize}</h1>
This will find text defined in U.S. English and not yet translated to other languages.  
Leaving new text blank allows you to easily find translation work with a search.
<br>

{if count($codes) > 1}
<div style="margin:5px;padding:5px;height:150px;border:1px solid rgb(200,200,200);background-color:rgb(250,250,250);overflow:auto;">
<table cellspacing="0" cellpadding="2" border="0">
<tr>
	<td><b>Language</td>
	<td style="padding-left:10px;"><b>With new text to translate...</td>
</tr>
{foreach from=$codes key=code item=lang_name}
{if $code != 'en_US'}
	<tr>
	<td>
		{$lang_name}
		<input type="hidden" name="lang_codes[]" value="{$code}">
	</td>
	
	<td style="padding-left:10px;">
		<select name="lang_actions[]">
			<option value="">- leave blank -</option>
			<option value="en_US">Copy U.S. English</option>
		</select>
	</td>
	</tr>
{/if}
{/foreach}
</table>
</div>
{else}
<br>
<b>You have no non-English languages defined.</b><br>
<br>
{/if}

{if count($codes) > 1}<button type="submit"><img src="{devblocks_url}c=resource&p=portsensor.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>{/if}
<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=portsensor.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>

</form>


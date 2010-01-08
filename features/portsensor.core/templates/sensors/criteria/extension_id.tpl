<b>{'search.operator'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">{'search.oper.in_list'|devblocks_translate}</option>
		<option value="not in">{'search.oper.in_list.not'|devblocks_translate}</option>
	</select>
</blockquote>

<b>{'sensor.extension_id'|devblocks_translate|capitalize}:</b><br>
{foreach from=$sensor_type_mfts item=sensor_type_mft key=extension_id}
<label><input name="sensor_types[]" type="checkbox" value="{$extension_id}"><span style="color:rgb(0,120,0);">{$sensor_type_mft->name}</span></label><br>
{/foreach}


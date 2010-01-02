<b>{$translate->_('search.operator')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">{$translate->_('search.oper.in_list')}</option>
		<option value="not in">{$translate->_('search.oper.in_list.not')}</option>
	</select>
</blockquote>

<b>{$translate->_('sensor.status')|capitalize}:</b><br>
<label><input name="statuses[]" type="checkbox" value="0"><span class="status_ok">OK</span></label><br>
<label><input name="statuses[]" type="checkbox" value="1"><span class="status_warning">WARNING</span></label><br>
<label><input name="statuses[]" type="checkbox" value="2"><span class="status_critical">CRITICAL</span></label><br>
<label><input name="statuses[]" type="checkbox" value="3"><span class="status_critical">M.I.A.</span></label><br>


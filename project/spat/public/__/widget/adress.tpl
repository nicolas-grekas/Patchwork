<!-- SET a$inputFormat -->{g$inputFormat}<!-- END:SET -->
<!-- SET g$inputFormat --><tr><td nowrap="nowrap">%0{"&nbsp;:"}</td><td width="100%">%1%2</td></tr><!-- END:SET -->

<!-- AGENT 'input' _caption_="Société/Institution" _argv_=a$company -->
<!-- AGENT 'input' _caption_="Adresse" _argv_=a$adress -->
<tr>
	<td>{"Code postal / Ville"}{"&nbsp;:"}</td>
	<td>
	<table border="0" cellpadding="0" cellspacing="0" width="100%">
	<tr>
		<td width="30%"><!-- AGENT 'input' _argv_=a$zipcode _format_='%1%2' style='width:100%' --></td>
		<td>&nbsp;</td>
		<td width="70%"><!-- AGENT 'input' _argv_=a$city _format_='%1%2' style='width:100%' --></td>
	</tr>
	</table>
	</td>
</tr>
<!-- AGENT 'QSelect/input' _caption_="Pays" _argv_=a$country -->

<!-- SET g$inputFormat -->{a$inputFormat}<!-- END:SET -->

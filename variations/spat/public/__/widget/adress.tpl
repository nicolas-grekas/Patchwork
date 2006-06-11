<!-- SET a$inputFormat -->{g$inputFormat}<!-- END:SET -->
<!-- SET g$inputFormat --><tr><td nowrap="nowrap">%0{"&nbsp;:"}</td><td width="100%">%1%2</td></tr><!-- END:SET -->

<!-- AGENT a$company _caption_="Société/Institution" -->
<!-- AGENT a$adress  _caption_="Adresse" -->
<tr>
	<td>{"Code postal / Ville"}{"&nbsp;:"}</td>
	<td>
	<table border="0" cellpadding="0" cellspacing="0" width="100%">
	<tr>
		<td width="30%"><!-- AGENT a$zipcode _format_='%1%2' style='width:100%' --></td>
		<td>&nbsp;</td>
		<td width="70%"><!-- AGENT a$city    _format_='%1%2' style='width:100%' --></td>
	</tr>
	</table>
	</td>
</tr>
<!-- AGENT a$country _caption_="Pays" -->

<!-- SET g$inputFormat -->{a$inputFormat}<!-- END:SET -->

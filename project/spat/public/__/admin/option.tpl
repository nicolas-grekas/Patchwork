<!-- AGENT 'header' title="Définition des option d'inscription" form=$form -->

<table>
<tr>
	<td width="300" valign="top">
	<!-- AGENT 'admin/option/dtree' name='d' -->
	</td>
	<td valign="top">

<!-- IF !g$__1__ || g$__2__ -->

<!-- IF g$__2__ -->
Edition d'un choix d'option
<table>
<!-- AGENT 'input' _caption_="Intitulé" _argv_=$edit_label -->
<!-- AGENT 'input' _caption_="PU HT" _argv_=$edit_amount -->
<!-- AGENT 'input' _caption_="PU HT majoré" _argv_=$edit_amount_raised -->
<tr><td colspan="2" align="right"><!-- AGENT 'input' _argv_=$edit_submit value="Enregistrer" _format_='%1' --></td></tr>
<tr><td colspan="2" align="right"><!-- AGENT 'input' _argv_=$edit_del value="Supprimer" _format_='%1' --></td></tr>
</table>
<!-- END:IF -->

<!-- IF $new_submit -->
<table class="option" cellspacing="0">
<tr>
	<th width="100%">{"Option"}</th>
	<th>{"Type"}</th>
	<th>{"TVA"}</th>
	<th></th>
</tr>
<!-- LOOP $option -->
<tr>
	<td>{$label}</td>
	<td><table><tr><td><img src="img/{$type}.gif" /></td><td>{$type_label}</td></tr></table></td>
	<td>{$tax_label}</td>
	<td nowrap="nowrap" style="white-space:nowrap">
		<!-- AGENT 'input' type='image' _argv_=$edit_up src='img/up.gif' title="Monter cette option dans la liste" _format_='%1' -->
		<!-- AGENT 'input' type='image' _argv_=$edit_down src='img/down.gif' title="Descendre cette option dans la liste" _format_='%1' -->
	</td>
</tr>
<!-- END:LOOP -->
<tr>
	<td><!-- AGENT 'input' _argv_=$new_label _format_='%1%2' --></td>
	<td><!-- AGENT 'input' _argv_=$new_type _format_='%1%2' --></td>
	<td><!-- AGENT 'input' _argv_=$new_tax_id _format_='%1%2' --></td>
	<td><!-- AGENT 'input' _argv_=$new_submit value="Ajouter" _format_='%1' --></td>
</tr>
</table>
<!-- END:IF -->

<!-- ELSE -->

Edition d'une option
<table>
<!-- AGENT 'input' _caption_="Intitulé" _argv_=$edit_label -->
<!-- IF $edit_tax_id -->
<!-- AGENT 'input' _caption_="TVA" _argv_=$edit_tax_id -->
<!-- END:IF -->
<!-- IF $quantity_label -->
<!-- AGENT 'input' _caption_="PU HT" _argv_=$quantity_amount -->
<!-- AGENT 'input' _caption_="PU HT majoré" _argv_=$quantity_amount_raised -->
<!-- AGENT 'input' _caption_="Intitulé de répétition" _argv_=$quantity_label -->
<!-- END:IF -->
<tr>
	<td>{"Type"}{"&nbsp;:"}</td>
	<td><table><tr><td><img src="img/{$type}.gif" /></td><td>{$type_label}</td></tr></table></td>
</tr>
<tr><td colspan="2" align="right"><!-- AGENT 'input' _argv_=$edit_submit value="Enregistrer" _format_='%1' --></td></tr>
<tr><td colspan="2" align="right"><!-- AGENT 'input' _argv_=$edit_del value="Supprimer" _format_='%1' --></td></tr>
</table>
<!-- IF $new_submit -->

		<table>
		<tr>
			<th width="100%">{"Choix"}</th>
			<th nowrap="nowrap">{"PU HT (-)"}</th>
			<th nowrap="nowrap">{"PU HT (+)"}</th>
			<th></th>
		</tr>
		<!-- LOOP $choice -->
		<tr>
			<td>{$label}</td>
			<td>{$amount}</td>
			<td>{$amount_raised}</td>
			<td nowrap="nowrap">
				<!-- AGENT 'input' type='image' _argv_=$edit_up src='img/up.gif' title="Monter ce choix dans la liste" _format_='%1' -->
				<!-- AGENT 'input' type='image' _argv_=$edit_down src='img/down.gif' title="Descendre ce choix dans la liste" _format_='%1' -->
			</td>
		</tr>
		<!-- END:LOOP -->
		<tr>
			<td><!-- AGENT 'input' _argv_=$new_label _format_='%1%2' --></td>
			<td><!-- AGENT 'input' _argv_=$new_amount _format_='%1%2' --></td>
			<td><!-- AGENT 'input' _argv_=$new_amount_raised _format_='%1%2' --></td>
			<td><!-- AGENT 'input' _argv_=$new_submit value="Ajouter" _format_='%1' --></td>
		</tr>
		</table>

<!-- END:IF -->

<!-- END:IF -->

	</td>
</tr>
</table>

<!-- AGENT 'footer' form=$form -->

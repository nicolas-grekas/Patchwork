<!-- AGENT 'header' title="Edition d'une option d'inscription" form=$form -->


<fieldset><legend>{"Modifier l'option"}</legend>
<table>
<!-- AGENT 'input' _argv_=$edit_label       _caption_="Intitulé" -->
<!-- AGENT 'input' _argv_=$edit_min_default _caption_="Min" -->
<!-- AGENT 'input' _argv_=$edit_max_default _caption_="Max" -->
<!-- AGENT 'input' _argv_=$edit_tax_id      _caption_="TVA" -->
<!-- AGENT 'input' _argv_=$edit_admin_only  _caption_="Privé" -->
<tr>
	<td></td>
	<td><!-- AGENT 'input' _argv_=$edit_submit _format_='%1' value="Enregistrer" --></td>
</tr>
</table>
</fieldset>

<!-- IF $choice -->
<fieldset><legend>{"Modifier un choix"}</legend>
<table>
<tr>
	<th></th>
	<th>{"Intitulé"}</th>
	<th>{"PU HT"}</th>
	<th>{"PU HT+"}</th>
	<th>{"Quota"}</th>
	<th>{"Utilisé"}</th>
	<th>{"Privé"}</th>
</tr>
<!-- LOOP $choice -->
<tr>
	<td><input type="radio" name="f_choice" value="{$choice_id}"></td>
	<td>{$label}</td>
	<td>{$price_default}</td>
	<td>{$upper_price_default}</td>
	<td>{$quota_max}</td>
	<td>{$quota_used}</td>
	<td>{$admin_only|test:'X':''}</td>
</tr>
<!-- END:LOOP -->
</table>

{"Pour la sélection"}{"&nbsp;:"}
	<!-- AGENT 'input' _argv_=$f_up _format_='%1' value="Monter" -->
	<!-- AGENT 'input' _argv_=$f_down _format_='%1' value="Descendre" -->
	<!-- AGENT 'input' _argv_=$f_del _format_='%1' value="Supprimer" -->
</fieldset>
<!-- END:IF -->

<fieldset><legend>{"Ajouter un choix"}</legend>
<table>
<!-- AGENT 'input' _argv_=$new_label               _caption_="Intitulé" -->
<!-- AGENT 'input' _argv_=$new_price_default       _caption_="PU HT par défaut" -->
<!-- AGENT 'input' _argv_=$new_upper_price_default _caption_="PU HT majoré par défaut" -->
<!-- AGENT 'input' _argv_=$new_quota_max           _caption_="Quota" -->
<!-- AGENT 'input' _argv_=$new_admin_only          _caption_="Privé" -->
<tr>
	<td></td>
	<td><!-- AGENT 'input' _argv_=$new_submit _format_='%1' value="Ajouter" --></td>
</tr>
</table>
</fieldset>

<!-- AGENT 'footer' form=$form -->

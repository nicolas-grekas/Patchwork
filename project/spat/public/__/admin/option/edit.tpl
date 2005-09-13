<!-- AGENT 'header' title="Edition d'une option d'inscription" form=$form -->


<fieldset><legend>{"Modifier l'option"}</legend>
<table>
<!-- AGENT $edit_label       _caption_="Intitulé" -->
<!-- AGENT $edit_min_default _caption_="Min" -->
<!-- AGENT $edit_max_default _caption_="Max" -->
<!-- AGENT $edit_tax_id      _caption_="TVA" -->
<!-- AGENT $edit_admin_only  _caption_="Privé" -->
<tr>
	<td></td>
	<td><!-- AGENT $edit_submit _format_='%1' value="Enregistrer" --></td>
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
	<!-- AGENT $f_up _format_='%1' value="Monter" -->
	<!-- AGENT $f_down _format_='%1' value="Descendre" -->
	<!-- AGENT $f_del _format_='%1' value="Supprimer" -->
</fieldset>
<!-- END:IF -->

<fieldset><legend>{"Ajouter un choix"}</legend>
<table>
<!-- AGENT $new_label               _caption_="Intitulé" -->
<!-- AGENT $new_price_default       _caption_="PU HT par défaut" -->
<!-- AGENT $new_upper_price_default _caption_="PU HT majoré par défaut" -->
<!-- AGENT $new_quota_max           _caption_="Quota" -->
<!-- AGENT $new_admin_only          _caption_="Privé" -->
<tr>
	<td></td>
	<td><!-- AGENT $new_submit _format_='%1' value="Ajouter" --></td>
</tr>
</table>
</fieldset>

<!-- AGENT 'footer' form=$form -->

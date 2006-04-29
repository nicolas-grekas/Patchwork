<!-- AGENT 'header' title="Gestion des options d'inscription" form=$form -->

<!-- IF $option -->
<table>
<!-- LOOP $option -->
<tr>
	<td><input type="radio" name="f_option" value="{$option_id}"></td>
	<td><a href="{~}{g$__AGENT__}edit/{$option_id}">{$label}</a></td>
</tr>
<!-- IF $choice -->
<tr>
	<td></td>
	<td>
		<table>
		<!-- LOOP $choice -->
		<tr>
			<td>{$label}</td>
		</tr>
		<!-- END:LOOP -->
		</table>
	</td>
</tr>
<!-- END:IF -->
<!-- END:LOOP -->
</table>

{"Pour la sélection"}{"&nbsp;:"}
	<!-- AGENT $f_up _format_='%1' value="Monter" -->
	<!-- AGENT $f_down _format_='%1' value="Descendre" -->
	<!-- AGENT $f_clone _format_='%1' value="Cloner" -->
	<!-- AGENT $f_del _format_='%1' value="Supprimer" -->
<!-- END:IF -->

<fieldset><legend>{"Ajouter une nouvelle option"}</legend>
<table>
<!-- AGENT $f_type       _caption_="Type" -->
<!-- AGENT $f_label      _caption_="Intitulé" -->
<!-- AGENT $f_tax_id     _caption_="TVA (si applicable)" -->
<!-- AGENT $f_admin_only _caption_="Privé" -->
<tr>
	<td></td>
	<td><!-- AGENT $f_submit _format_='%1' value="Ajouter" --></td>
</tr>
</table>
</fieldset>
<!-- AGENT 'footer' form=$form -->

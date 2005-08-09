<!-- AGENT 'header' title = "Inscription groupée" form = $form -->

<div id="formDiv">

<table>
<tr>
	<td>
		<fieldset><legend>{"Contact"}</legend>
		<table>
		<tr>
			<td colspan="2">{$f_contact_firstname} {$f_contact_lastname}</td>
		</tr>
		<tr><td>&nbsp;</td></tr>
		<tr>
			<td>{"Email"}{"&nbsp;:"} </td>
			<td>{$f_contact_email|default:'-'}</td>
		</tr>
		<tr>
			<td>{"Tél."}{"&nbsp;:"} </td>
			<td>{$f_contact_phone|default:'-'}</td>
		</tr>
		<tr>
			<td>{"Fax."}{"&nbsp;:"} </td>
			<td>{$f_contact_fax|default:'-'}</td>
		</tr>
		</table>
		</fieldset>
	</td>
	<td>
		<fieldset><legend>{"Coordonnées du groupe"}</legend>
		{$f_company}<br />
		{$f_adress|replace:'[\r\n]+':'<br />'}<br />
		{$f_zipcode} {$f_city}<!-- IF $f_country -->, {$f_country}<!-- END:IF -->
		</fieldset>
	</td>
	<td>
		<fieldset><legend>{"Adresse de facturation"}</legend>
		<!-- IF $f_fact_zipcode -->
		{$f_fact_company}<br />
		{$f_fact_adress|replace:'[\r\n]+':'<br />'}<br />
		{$f_fact_zipcode} {$f_fact_city}<!-- IF $f_country -->, {$f_fact_country}<!-- END:IF -->
		<!-- ELSE -->
		{$f_company}<br />
		{$f_adress|replace:'[\r\n]+':'<br />'}<br />
		{$f_zipcode} {$f_city}<!-- IF $f_country -->, {$f_country}<!-- END:IF -->
		<!-- END:IF -->
		<br /><br />
		{"VAT Number"}{"&nbsp;:"} {$f_fact_vat|default:'-'}
		</fieldset>
	</td>
</tr>
</table>

<fieldset><legend>{"Options souscrites"}</legend>
<table>
<tr>
	<th>{"Option"}</th>
	<th>{"PU HT"} ({g$currency})</th>
	<th>{"Quantités"}</th>
	<th>{"TVA (%)"}</th>
	<th>{"Prix HT"} ({g$currency})</th>
	<th>{"TVA"} ({g$currency})</th>
	<th>{"Prix TTC"} ({g$currency})</th>
</tr>
<tr>
	<th></th>
	<th></th>
	<th></th>
	<th></th>
	<th>{"Total HT"} ({g$currency})</th>
	<th>{"Total TVA"} ({g$currency})</th>
	<th>{"Total TTC"} ({g$currency})</th>
</tr>
</table>
</fieldset>

<fieldset><legend>{"Choix du mode de règlement"}</legend>
</fieldset>

<!-- AGENT 'widget/prevnext'
	prevurl = "{g$__AGENT__}../member/"
	submit = $f_submit
-->

</div>

<!-- AGENT 'footer' form = $form -->

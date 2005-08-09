<!-- AGENT 'header' title = "Inscription individuelle" form = $form -->

<div id="formDiv">

<table>
<tr>
	<td>
		<fieldset><legend>{"Vos coordonnées"}</legend>
		<table>
		<tr>
			<td colspan="2">
			{$f_firstname} {$f_lastname}<br />
			{$f_company}<br />
			{$f_adress|replace:'[\r\n]+':'<br />'}<br />
			{$f_zipcode} {$f_city}<!-- IF $f_country -->, {$f_country}<!-- END:IF -->
			</td>
		</tr>
		<tr><td>&nbsp;</td></tr>
		<tr>
			<td>{"Email"}{"&nbsp;:"} </td>
			<td>{$f_email|default:'-'}</td>
		</tr>
		<tr>
			<td>{"Tél."}{"&nbsp;:"} </td>
			<td>{$f_phone|default:'-'}</td>
		</tr>
		<tr>
			<td>{"Fax."}{"&nbsp;:"} </td>
			<td>{$f_fax|default:'-'}</td>
		</tr>
		</table>
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
	prevurl = "{g$__AGENT__}../"
	submit = $f_submit
-->

</div>

<!-- AGENT 'footer' form = $form -->

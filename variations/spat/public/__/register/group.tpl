<!-- AGENT 'header' title = "Inscription groupée" form = $form -->

<div id="formDiv">

<fieldset><legend>{"Coordonnées du groupe"}</legend>
<table width="80%" align="center">
<!-- AGENT 'widget/adress'
	company = $f_company
	adress = $f_adress
	zipcode = $f_zipcode
	city = $f_city
	country = $f_country
-->
</table>
</fieldset>

<fieldset><legend>{"Contact"}</legend>
<table width="80%" align="center">
<!-- AGENT 'widget/coo'
	lastname = $f_contact_lastname
	firstname = $f_contact_firstname
	email = $f_contact_email
	phone = $f_contact_phone
	fax = $f_contact_fax
-->
</table>
</fieldset>

<fieldset><legend>{"Adresse de facturation"}</legend>
<div class="legend">{"Si différente de l'adresse du groupe."}</div>
<table width="80%" align="center">
<!-- AGENT 'widget/adress'
	company = $f_fact_company
	adress = $f_fact_adress
	zipcode = $f_fact_zipcode
	city = $f_fact_city
	country = $f_fact_country
-->
<!-- AGENT $f_fact_vat _caption_="VAT Number" -->
</table>
</fieldset>

<!-- AGENT 'widget/prevnext'
	prevurl = "{g$__AGENT__}../"
	submit = $f_submit
-->

</div>

<!-- AGENT 'footer' form = $form -->
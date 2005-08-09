<!-- AGENT 'header' title = "Inscription individuelle" form = $form -->

<div id="formDiv">

<!-- IF $member --><!--

LOOP $member --><!--
	IF a+1$memberCount -->, <!-- END:IF
	-->{$lastname} {$firstname}<!--
END:LOOP -->.

<!-- ELSE -->
<fieldset><legend>{"Coordonnées"}</legend>
<table width="80%" align="center">
<!-- AGENT 'widget/coo'
	lastname = $f_lastname
	firstname = $f_firstname
	email = $f_email
	phone = $f_phone
	fax = $f_fax
-->
<tr><td colspan="2">&nbsp;<br /><div class="legend">{"S'il y a une différence avec le reste du groupe :"}</div></td></tr>
<!-- AGENT 'widget/adress'
	company = $f_company
	adress = $f_adress
	zipcode = $f_zipcode
	city = $f_city
	country = $f_country
-->
</table>
</fieldset>
<!-- END:IF -->

<fieldset><legend>{"Options"}</legend>
<table width="80%" align="center">
<!-- AGENT 'widget/option'
	option = $option
-->
</table>
</fieldset>

<!-- SET $next --><!-- IF !$finalStep -->{"Enregistrer"}<!-- END:IF --><!-- END:SET -->

<!-- AGENT 'widget/prevnext'
	next = $next
	prevurl = "{g$__AGENT__}../member/"
	submit = $f_submit
-->

</div>

<!-- AGENT 'footer' form = $form -->

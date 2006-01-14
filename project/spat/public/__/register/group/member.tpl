<!-- AGENT 'header' title = "Inscription groupée" form = $form -->
<!-- SET g$inputFormat -->%1<!-- END:SET -->

<script type="text/javascript"><!--
function clickDrop($id)
{
	--$id;
	var $check = lF['f_check[]'], $i = 0;
	for (; $i < $check.length; ++$i) $check[$i].checked = $i==$id;
	lF.f_del.click();
}
//--></script>

<div id="formDiv">

<!-- IF $member -->
<table>
<caption>{"%d membres dans le groupe"|printf:$member}</caption>
<!-- SET a$head -->
<tr>
	<th colspan="3"></th>
	<th>{"Nom"}</th>
	<th>{"Prénom"}</th>
	<th>{"Email"}</th>
</tr>
<!-- END:SET -->
<!-- LOOP $member -->
<!-- IF !(a+1$memberCount%30) -->{a$head}<!-- END:IF -->
<tr bgcolor="{'member_cycle'|cycle:'silver':''}">
	<td><input type="checkbox" name="f_check[]" value="{a$memberCount}" /></td>
	<td><a href="register/group/edit/{a$memberCount}"><img src="img/edit.gif" border="0" title="Modifier" /></a></td>
	<td><a href="javascript:;" onclick="clickDrop({a$memberCount})"><img src="img/drop.gif" border="0" title="Supprimer" /></a></td>
	<td>{$lastname}</td>
	<td>{$firstname}</td>
	<td>{$email}</td>
</tr>
<!-- END:LOOP -->
</table>
{"Pour la sélection"}{"&nbsp;:"}
<!-- AGENT $f_edit value="Modifier les options" -->
<!-- AGENT $f_del  value="Supprimer" onclick="return confirm({\"Voulez-vous vraiement supprimer la sélection ?\"|escape:'js'})" -->
<!-- AGENT $f_check _format_='%2' -->

<!-- END:IF -->


<fieldset><legend>{"Ajouter des membres"}</legend>
<div class="legend">{"Un membre par ligne, au format <b>Nom,Prénom,email</b>. L'email permettra à chaque membre de recevoir une notification et de souscrire des options supplémentaires à titre personnel."}</div>
<table width="80%" align="center">
<tr><td><!-- AGENT $f_member style='height:100px' --></td></tr>
<!-- IF $member --><tr><td><!-- AGENT $f_add value="Ajouter" style='float:right' --></td></tr><!-- END:IF -->
</table>
</fieldset>

<!-- AGENT 'widget/prevnext'
	prevurl = "{g$__AGENT__}../"
	submit = $f_submit
-->

</div>

<!-- AGENT 'footer' form = $form -->

<!-- AGENT 'header' title = 'Annuaire des pineurs 2005' -->

<!-- SET a$blank --><!-- END:SET -->

<script src="js/QJsrs"></script>

<style>
a
{
	color: black;
	text-decoration: none
}

table
{
	background-color: silver;
}

td, th, body
{
	text-align: center;
	font-size: 11px;
	font-family: Verdana;
	white-space: nowrap;
	background-color: white;
}

#editDiv
{
	position: absolute;
	visibility: hidden;
	border: 1px solid red;
	background-color: white;
}

#editDiv textarea
{
	border: 0px solid;
}

body
{
	text-align: left;
	margin: 0px;
}
</style>

<script><!--
function editMe(link, id, key)
{
	var div = document.getElementById('editDiv'),
		form = document.forms[0],
		txt = form[0],

		left = link.offsetLeft,
		top = link.offsetTop,
		parent = link.offsetParent,
		oldValue;

	while (parent)
	{
		left += parent.offsetLeft;
		top += parent.offsetTop;
		parent = parent.offsetParent;
	}

	left = Math.min(left, (window.innerWidth || document.body.offsetWidth) - div.offsetWidth - 25);
	left = Math.max(0, left);

	div.style.left = left + 'px';
	div.style.top = top + 'px';

	div.style.visibility = 'visible';
	oldValue = txt.value = link.innerHTML.replace(
			/<br[^>]*>/gi, "\n").replace(
			/&#039;/g, "'").replace(
			/&quot;/g, '"').replace(
			/{a$blank}/g, '').replace(
			/&gt;/g, '>').replace(
			/&lt;/g, '<').replace(
			/&amp;/g, '&');

	txt.onkeydown = function(e)
	{
		if (27 == (e || event).keyCode) this.value = oldValue, this.blur();
	}

	txt.onblur = function()
	{
		if (oldValue != this.value) (new QJsrs('QJsrs/save', true)).pushCall({ID:id,KEY:key,DATA:this.value}, function(){});
		div.style.visibility = 'hidden';
		link.innerHTML = this.value.replace(
			/^\s+/, '').replace(
			/\s+$/, '').replace(
			/&/g, '&amp;').replace(
			/</g, '&lt;').replace(
			/>/g, '&gt;').replace(
			/"/g, '&quot;').replace(
			/'/g, '&#039;').replace(
			/\n/g, '<br>');

		if (link.innerHTML == '') link.innerHTML = '{a$blank}';
	}

	txt.focus();
}
//--></script>
<form accept-charset="UTF-8" onsubmit="return false"><div id="editDiv"><textarea name="editTxt" cols="40" rows="2"></textarea></div>
&nbsp;<i style="color: red">Double-Cliques au milieu d'une case pour la modifier.</i>
<table border=0 cellspacing=1 cellpadding=2>
<tr>
<!-- SET a$counter -->0<!-- END:SET -->
<!-- LOOP $PINEURS -->
	<!-- IF !(a+1$counter % 10) --><th>Nom</th><!-- END:IF -->
	<td ondblclick="editMe(this,{$id|default:0},'nom')">{$nom|replace:'\n':'<br>'|default:a$blank}</td>
<!-- END:LOOP -->
</tr>
<tr>
<!-- SET a$counter -->0<!-- END:SET -->
<!-- LOOP $PINEURS -->
	<!-- IF !(a+1$counter % 10) --><th>Prénom</th><!-- END:IF -->
	<td ondblclick="editMe(this,{$id|default:0},'prenom')">{$prenom|replace:'\n':'<br>'|default:a$blank}</td>
<!-- END:LOOP -->
</tr>
<tr>
<!-- SET a$counter -->0<!-- END:SET -->
<!-- LOOP $PINEURS -->
	<!-- IF !(a+1$counter % 10) --><th>Email</th><!-- END:IF -->
	<td ondblclick="editMe(this,{$id|default:0},'email')">{$email|replace:'\n':'<br>'|default:a$blank}</td>
<!-- END:LOOP -->
</tr>
<tr>
<!-- SET a$counter -->0<!-- END:SET -->
<!-- LOOP $PINEURS -->
	<!-- IF !(a+1$counter % 10) --><th>Portable</th><!-- END:IF -->
	<td ondblclick="editMe(this,{$id|default:0},'tel_port')">{$tel_port|replace:'\n':'<br>'|default:a$blank}</td>
<!-- END:LOOP -->
</tr>
<tr>
<!-- SET a$counter -->0<!-- END:SET -->
<!-- LOOP $PINEURS -->
	<!-- IF !(a+1$counter % 10) --><th>Tél. fixe</th><!-- END:IF -->
	<td ondblclick="editMe(this,{$id|default:0},'tel_fixe')">{$tel_fixe|replace:'\n':'<br>'|default:a$blank}</td>
<!-- END:LOOP -->
</tr>
<tr>
<!-- SET a$counter -->0<!-- END:SET -->
<!-- LOOP $PINEURS -->
	<!-- IF !(a+1$counter % 10) --><th>Tél. parent</th><!-- END:IF -->
	<td ondblclick="editMe(this,{$id|default:0},'tel_parent')">{$tel_parent|replace:'\n':'<br>'|default:a$blank}</td>
<!-- END:LOOP -->
</tr>
<tr>
<!-- SET a$counter -->0<!-- END:SET -->
<!-- LOOP $PINEURS -->
	<!-- IF !(a+1$counter % 10) --><th>Adresse</th><!-- END:IF -->
	<td ondblclick="editMe(this,{$id|default:0},'adresse')">{$adresse|replace:'\n':'<br>'|default:a$blank}</td>
<!-- END:LOOP -->
</tr>
<tr>
<!-- SET a$counter -->0<!-- END:SET -->
<!-- LOOP $PINEURS -->
	<!-- IF !(a+1$counter % 10) --><th>Adr. parent</th><!-- END:IF -->
	<td ondblclick="editMe(this,{$id|default:0},'adr_parent')">{$adr_parent|replace:'\n':'<br>'|default:a$blank}</td>
<!-- END:LOOP -->
</tr>
<tr>
<!-- SET a$counter -->0<!-- END:SET -->
<!-- LOOP $PINEURS -->
	<!-- IF !(a+1$counter % 10) --><th>Date de naissance</th><!-- END:IF -->
	<td ondblclick="editMe(this,{$id|default:0},'birthday')">{$birthday|replace:'\n':'<br>'|default:a$blank}</td>
<!-- END:LOOP -->
</tr>
<tr>
<!-- SET a$counter -->0<!-- END:SET -->
<!-- LOOP $PINEURS -->
	<!-- IF !(a+1$counter % 10) --><th>Activité professionnelle</th><!-- END:IF -->
	<td ondblclick="editMe(this,{$id|default:0},'activite')">{$activite|replace:'\n':'<br>'|default:a$blank}</td>
<!-- END:LOOP -->
</tr>
<tr>
<!-- SET a$counter -->0<!-- END:SET -->
<!-- LOOP $PINEURS -->
	<!-- IF !(a+1$counter % 10) --><th>Actualité personnelle</th><!-- END:IF -->
	<td ondblclick="editMe(this,{$id|default:0},'actu')">{$actu|replace:'\n':'<br>'|default:a$blank}</td>
<!-- END:LOOP -->
</tr>
<tr>
<!-- SET a$counter -->0<!-- END:SET -->
<!-- LOOP $PINEURS -->
	<!-- IF !(a+1$counter % 10) --><th>Autre</th><!-- END:IF -->
	<td ondblclick="editMe(this,{$id|default:0},'autre')">{$autre|replace:'\n':'<br>'|default:a$blank}</td>
<!-- END:LOOP -->
</tr>
</table>
</form>
<!-- AGENT 'footer' -->

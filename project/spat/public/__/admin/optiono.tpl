<!-- AGENT 'header' title="Définition des option d'inscription" -->

<!-- SET g$inputFormat -->%1<!-- END:SET -->

<script><!--

function showBlock(id, type)
{
	id = document.getElementById((type?'option':'choice') + id);
	id.style.display = id.style.display=='none' ? '' : 'none';
}

shownForm = {style:{}};
function showForm(id)
{
	id = document.getElementById(id);
	if (id == shownForm)
	{
		id.style.display = id.style.display=='none' ? '' : 'none';
	}
	else
	{
		shownForm.style.display = 'none';
		id.style.display = '';
		shownForm = id;
	}
}

//--></script>

<style>

body, td, th, input, select
{
	font-size: 80%;
}

th
{
	white-space: nowrap;
	font-size: 60%;
	font-style: italic;
}

table
{
	width: 100%;
}

table.option
{
	background-color: silver;
}

table.choice
{
	background-color: white;
}

input.text
{
	width: 100%;
	background-color: transparent;
	border: 0px solid;
}

.expr
{
	width: 75px;
}

</style>

<div style="width: 800px">
<!-- AGENT 'form' _argv_=$form _enterControl_=1 -->
<!-- AGENT "{g$__AGENT__}recursiveList" -->
<!-- AGENT 'form' _argv_=$form _mode_='close' -->
</div>

<!-- AGENT 'footer' -->

<!--*

This template is called automatically when a form with a file element is submitted

*-->
<html lang="{g$__LANG__}">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>{"Envoi en cours"} ...</title>

<style type="text/css">

body
{
	margin: 10px;
	padding: 0px;
}

body, td
{
	background-color: #EEEEEE;
	font-size: 12px;
	font-family: Verdana;
}

#progress
{
	width: 100%;
	background-color: white;
}

#detail
{
	position: absolute;
	width: 100%;
	text-align: center;
	padding: 2px;
}

table
{
	margin: 5px;
}

</style>
</head>
<body>

<div id="sending">{"Envoi en cours"} ...</div>

<table border="0" cellspacing="0" cellpadding="0">
<tr>
	<td rowspan="3"><img src="{~}img/upload/l.png" width="2" height="29" /></td>
	<td background="img/upload/t.gif"><img src="{~}img/upload/t.gif" width="1" height="3" /></td>
	<td rowspan="3"><img src="{~}img/upload/r.png" width="5" height="29" /></td>
</tr>
<tr>
	<td width="100%"><div id="detail"></div><div id="progress"><img src="{~}img/upload/i.gif" id="unit" width="8" height="20" /></div></td>
</tr>
<tr>
	<td id="b" background="{~}img/upload/b.png" style="filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src={home:'img/upload/b.png'|js},sizingMethod='scale')"><img src="{~}img/blank.gif" width="1" height="6" /></td>
</tr>
</table>


<div id="remaining">{"Estimation du temps restant"} ...</div>

<script type="text/javascript" src="{~}js/liveAgent"></script>
<script type="text/javascript" src="{~}js/upload"></script>

</body>
</html>

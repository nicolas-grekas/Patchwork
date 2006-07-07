<?php

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: max-age=0,private,must-revalidate');

?><html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Debug Window</title>
<style type="text/css">
body
{
	margin: 0px;
	padding: 0px;
}
pre
{
	font-family: Arial;
	font-size: 10px;
	border-top: 1px solid;
	margin: 0px;
	padding: 5px;
}
pre:hover
{
	background-color: #D9E4EC;
}
</style>
<script type="text/javascript">/*<![CDATA[*/
function Z()
{
	scrollTo(0, window.innerHeight||(document.documentElement||document.body).scrollHeight);
}
//]]></script>
</head>
<body><?php

$sleep = 500;	// (ms)
$period = 5;	// (s)

ignore_user_abort(false);
@set_time_limit(0);

$error_log = ini_get('error_log');
$error_log = $error_log ? $error_log : './error.log';
echo str_repeat(' ', 512), // special MSIE
	'<pre>';
flush();

$sleep = max(100, (int) $sleep);
$i = $period = max(1, (int) 1000*$period / $sleep);
$sleep *= 1000;
while (1)
{
	clearstatcache();
	if (is_file($error_log))
	{
		echo '<b></b>'; // Test the connexion for "ignore_user_abort(false)"
		flush();

		readfile($error_log);
		echo '<script type="text/javascript">/*<![CDATA[*/Z()//]]></script>';
		flush();

		unlink($error_log);
	}
	else if (!--$i)
	{
		$i = $period;
		echo '<b></b>'; // Test the connexion for "ignore_user_abort(false)"
		flush();
	}

	if (isset($_GET['stop']))
	{
		echo '<script type="text/javascript">/*<![CDATA[*/scrollTo(0,0);if(window.opener&&opener.E&&opener.E.buffer)document.write(opener.E.buffer),opener.E.buffer=""//]]></script>';
		break;
	}

	usleep($sleep);
}

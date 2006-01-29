<?php

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: max-age=0,private,must-revalidate');

?><html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Debug Window</title>
<style>
body
{
	margin: 0px;
	padding: 0px;
}
pre
{
	font-family: Lucida Console;
	font-size: 9px;
	border-top: 1px solid;
	margin: 0px;
	padding: 5px;
}
pre:hover
{
	background-color: #D9E4EC;
}
</style>
<script>
function Z()
{
	scrollTo(0, window.innerHeight||document.body.scrollHeight);
}
</script>
</head>
<body><?php

$sleep = 500;	// (ms)
$period = 5;	// (s)

if (!isset($_GET['stop'])) apache_setenv('no-gzip', '1');
ignore_user_abort(false);
set_time_limit(0);

$error_log = ini_get('error_log');
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
		echo '<script>Z()</script>';
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
		echo '<script>scrollTo(0,0);if(window.opener&&opener.E&&opener.E.buffer)document.write(opener.E.buffer),opener.E.buffer=""</script>';
		break;
	}

	usleep($sleep);
}

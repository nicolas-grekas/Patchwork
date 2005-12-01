<?php

CIA::setMaxage(0);
CIA::setPrivate();
CIA::setExpires('onmaxage');

$context = '';

if (0 && !self::$handlesOb) 
{
	$msg = debug_backtrace();

	$context = array();
	$i = 0;
	$exit = count($msg);
	while ($i < $exit)
	{
		$a = array(
			' in   ' => @ "{$msg[$i]['file']} line {$msg[$i]['line']}",
			' call ' => @ (isset($msg[$i]['class']) ? $msg[$i]['class'].$msg[$i]['type'] : '') . $msg[$i]['function'] . '()'
		);

		if (isset($msg[$i]['args'])) $a[' args '] = $msg[$i]['args'];

		$context[$i++] = $a;
	}

	$context = CIA::htmlescape( print_r($context, true) );
}

$msg = '';
$exit = true;

switch ($code)
{
	case E_ERROR: $msg = '<b>Error</b>'; break;
	case E_USER_ERROR: $msg = '<b>User Error</b>'; break;
	case E_WARNING: $msg = '<b>Warning</b>'; break;
	case E_USER_WARNING: $msg = '<b>User Warning</b>'; break;
}

$msg || $exit = false;
if (!$msg) switch ($code)
{
	case E_NOTICE: $msg = '<b>Notice</b>'; break;
	case E_USER_NOTICE: $msg = '<b>User Notice</b>'; break;
	case E_STRICT: $msg = '<b>Strict Notice</b>'; break;				
	default: $msg = '<b>Unknown Error ('.$code.')</b>';
}

$cid = CIA::uniqid();
$i = fopen(ini_get('error_log'), 'ab');
fwrite($i, "<a href='javascript:;' onclick=\"var a=document.getElementById('{$cid}');a.style.display=a.style.display?'':'none';\">$msg</a> in <b>$file</b> line <b>$line</b>:\n$message<blockquote id='{$cid}' style='display: none'>Context : $context</blockquote><br><br>");
fclose($i);

if ($exit) exit;

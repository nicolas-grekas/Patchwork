<?php

self::setMaxage(0);
self::$private = true;
self::setExpires('onmaxage');

if (!function_exists('filterErrorArgs'))
{
	function filterErrorArgs($a, $k = true)
	{
		switch (gettype($a))
		{
			case 'object': return '(object) ' . get_class($a);

			case 'array':
				if ($k)
				{
					$b = array();

					foreach ($a as $k => &$v) $b[$k] = filterErrorArgs($v, false);
				}
				else $b = 'array(...)';

				return $b;

			case 'string': return '(string) ' . $a;

			case 'boolean': return $a ? 'true' : 'false';
		}

		return $a;
	}
}

$context = '';

if (!self::$handlesOb)
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

		if (
			in_array(
				$a[' call '],
				array(
					'CIA->error_handler()',
					'require()', 'require_once()',
					'include()', 'include_once()',
				)
			)
		)
		{
			++$i;
			continue;
		}

		if (isset($msg[$i]['args']) && $msg[$i]['args']) $a[' args '] = array_map('filterErrorArgs', $msg[$i]['args']);

		$context[$i++] = $a;
	}

	$context = htmlspecialchars( print_r($context, true) );
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

$cid = self::uniqid();
$cid = "<a href=\"javascript:;\" onclick=\"var a=document.getElementById('{$cid}');a.style.display=a.style.display?'':'none';\">$msg</a> in <b>$file</b> line <b>$line</b>:\n$message<blockquote id=\"{$cid}\" style=\"display:none\">Context : $context</blockquote><br><br>";

$i = ini_get('error_log');
$i = fopen($i ? $i : './error.log', 'ab');
flock($i, LOCK_EX);
fwrite($i, $cid, strlen($cid));
fclose($i);

if ($exit) exit;

<?php /*********************************************************************
 *
 *   Copyright : (C) 2006 Nicolas Grekas. All rights reserved.
 *   Email     : nicolas.grekas+patchwork@espci.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL, see COPYING
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


class extends CIA
{
	static $sleep = 500; // (ms)
	static $period = 5;  // (s)

	static function prolog()
	{
		$debugWin = CIA::$home . '_?d$&stop&' . mt_rand();
		$QDebug = CIA::$home . 'js/QDebug.js';
		$lang = CIA::__LANG__();

		return <<<EOHTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<script type="text/javascript">/*<![CDATA[*/
_____ = new Date/1;
onload = function() {
window.debugWin = open('$debugWin','debugWin','toolbar=no,status=yes,resizable=yes,scrollbars,width=320,height=240,left=' + parseInt(screen.availWidth - 340) + ',top=' + parseInt(screen.availHeight - 290));
if (!debugWin) alert('Disable anti-popup to use the Debug Window');
else E('Rendering time: ' + (new Date/1 - _____) + ' ms');
};
//]]></script>
<div style="position:fixed;_position:absolute;float:right;font-family:arial;font-size:9px;top:0px;right:0px;z-index:255"><a href="javascript:;" onclick="window.debugWin&&debugWin.focus()" style="background-color:blue;color:white;text-decoration:none;border:0px;" id="debugLink">Debug</a><script type="text/javascript" src="{$QDebug}"></script></div>
EOHTML;
	}

	static function display()
	{
		$S = isset($_GET['stop']);
		$S && ob_start('ob_gzhandler', 8192);

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
	border-top: 1px solid black;
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

		ignore_user_abort($S);
		@set_time_limit(0);

		ini_set('error_log', './error.log');
		$error_log = ini_get('error_log');
		$error_log = $error_log ? $error_log : './error.log';
		echo str_repeat(' ', 512), // special MSIE
			'<pre>';
		$S||flush();

		$sleep = max(100, (int) self::$sleep);
		$i = $period = max(1, (int) 1000*self::$period / $sleep);
		$sleep *= 1000;
		while (1)
		{
			clearstatcache();
			if (is_file($error_log))
			{
				echo '<b></b>'; // Test the connexion
				$S||flush();

				$h = @fopen($error_log, 'r');
				while (!feof($h))
				{
					$a = fgets($h);

					if ('[' == $a[0] && '] PHP ' == substr($a, 21, 6))
					{
						$b = strpos($a, ':', 28);
						$a = substr($a, 0, 23)
							. '<script type="text/javascript">/*<![CDATA[*/
		focus()
		L=opener&&opener.document.getElementById(\'debugLink\')
		L=L&&L.style
		if(L)
		{
		L.backgroundColor=\'red\'
		L.fontSize=\'18px\'
		}
		//]]></script><span style="color:red;font-weight:bold">'
							. substr($a, 23, $b-23)
							. '</span>'
							. preg_replace_callback(
								"'" . preg_quote(htmlspecialchars(CIA_PROJECT_PATH) . DIRECTORY_SEPARATOR . '.')
									. "([^\\\\/]+)\.[01]([0-9]+)(-?)\.{$GLOBALS['cia_paths_token']}\.zcache\.php'",
								array(__CLASS__, 'filename'),
								substr($a, $b)
							);
					}

					echo $a;
					if (connection_aborted()) break;
				}
				fclose($h);

				echo '<script type="text/javascript">/*<![CDATA[*/Z()//]]></script>';
				$S||flush();

				unlink($error_log);
			}
			else if (!--$i)
			{
				$i = $period;
				echo '<b></b>'; // Test the connexion
				$S||flush();
			}

			if ($S)
			{
				echo '<script type="text/javascript">/*<![CDATA[*/scrollTo(0,0);if(window.opener&&opener.E&&opener.E.buffer.length)document.write(opener.E.buffer.join("")),opener.E.buffer=[]//]]></script>';
				break;
			}

			usleep($sleep);
		}

		exit;
	}

	static function filename($m)
	{
		return $GLOBALS['cia_include_paths'][count($GLOBALS['cia_paths']) - ((int)($m[3].$m[2])) - 1]
			. DIRECTORY_SEPARATOR
			. str_replace('%1', '%', str_replace('%2', '_', strtr($m[1], '_', DIRECTORY_SEPARATOR)));
	}
}

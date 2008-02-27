<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


// Major browsers send a "Cache-Control: no-cache" only and only if a page is reloaded with
// CTRL+F5, CTRL+SHIFT+R or location.reload(true). Usefull to trigger synchronization events.
define(
	'PATCHWORK_SYNC_CACHE',
	file_exists('./.patchwork.php')
	&& filemtime('./config.patchwork.php') > filemtime('./.patchwork.php')
	|| (isset($_SERVER['HTTP_CACHE_CONTROL']) && 'no-cache' == $_SERVER['HTTP_CACHE_CONTROL'])
);

class
{
	static

	$sleep = 500, // (ms)
	$period = 5;  // (s)


	static function call()
	{
		$GLOBALS['patchwork_appId'] = -$GLOBALS['patchwork_appId'];

		PATCHWORK_DIRECT && isset($_GET['d$']) && self::sendDebugInfo();

		if (PATCHWORK_SYNC_CACHE && !PATCHWORK_DIRECT)
		{
			if ($h = @fopen('./.debugLock', 'xb'))
			{
				flock($h, LOCK_EX);

				@unlink('./.patchwork.php');

				global $patchwork_path;

				$offset = -12 - strlen(PATCHWORK_PATH_TOKEN);

				$dir = opendir('.');
				while (false !== $cache = readdir($dir)) if (preg_match('/^\..+\.[^0][^\.]+\.' . PATCHWORK_PATH_TOKEN . '\.zcache\.php$/D', $cache))
				{
					$cache = './' . $cache;
					$file = str_replace('%1', '%', str_replace('%2', '_', strtr(substr($cache, 3, $offset), '_', '/')));
					$level = substr(strrchr($file, '.'), 2);

					$file = substr($file, 0, -(2 + strlen($level)));
					if ('-' == substr($level, -1))
					{
						$level = -$level;
						$file = substr($file, 6);
					}

					$file = $patchwork_path[PATCHWORK_PATH_LEVEL - $level] . $file;

					if (!file_exists($file) || filemtime($file) >= filemtime($cache)) @unlink($cache);
				}
				closedir($dir);

				fclose($h);
			}
			else
			{
				$h = fopen('./.debugLock', 'rb');
				flock($h, LOCK_SH);
				fclose($h);
			}

			@unlink('./.debugLock');
		}
	}

	static function getProlog()
	{
		$QDebug   = p::__BASE__() . 'js/QDebug.js';

		return <<<EOHTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<script type="text/javascript" src="{$QDebug}"></script>
EOHTML;
	}

	static function getConclusion()
	{
		$debugWin = p::__BASE__() . '_?d$&stop';

		return <<<EOHTML
<script type="text/javascript">/*<![CDATA[*/E('Rendering time: ' + (new Date/1 - E.startTime) + ' ms');//]]></script>
<input type="hidden" name="debugStore" id="debugStore" value="" />
<div style="position:fixed;_position:absolute;top:0px;right:0px;background-color:white;visibility:hidden;height:100%" id="debugFrame"><iframe src="{$debugWin}" style="width:400px;height:100%"></iframe></div>
<div style="position:fixed;_position:absolute;top:0px;right:0px;z-index:255;font-family:arial;font-size:9px"><a href="javascript:;" onclick="var f=document.getElementById('debugFrame');if (f) f.style.visibility='hidden'==f.style.visibility?'visible':'hidden',document.getElementById('debugStore').value=f.style.visibility" style="background-color:blue;color:white;text-decoration:none;border:0px;" id="debugLink">Debug</a></div>
<script type="text/javascript">/*<![CDATA[*/setTimeout(function(){var f=document.getElementById('debugFrame'),s=document.getElementById('debugStore');if (f&&s&&s.value)f.style.visibility=s.value},0)//]]></script>
EOHTML;
	}

	static function sendDebugInfo()
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
div
{
	clear: both;
}
acronym
{
	width: 50px;
	text-align: right;
	float: left;
	clear: both;
	text-decoration: none;
	border-bottom: 0;
	font-style: italic;
	color: silver;
}
</style>
<script type="text/javascript">/*<![CDATA[*/

<?php if ($CONFIG['document.domain']) echo 'document.domain=', jsquote($CONFIG['document.domain']), ';' ?>

function Z()
{
	scrollTo(0, window.innerHeight||(document.documentElement||document.body).scrollHeight);
}
//]]></script>
</head>
<body><?php

		ignore_user_abort($S);
		set_time_limit(0);

		ini_set('error_log', './error.patchwork.log');
		$error_log = ini_get('error_log');
		$error_log = $error_log ? $error_log : './error.patchwork.log';
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
		L=parent&&parent.document.getElementById(\'debugLink\')
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
								"'" . preg_quote(htmlspecialchars(PATCHWORK_PROJECT_PATH) . '.')
									. '([^\\\\/]+)\.[01]([0-9]+)(-?)\.' . PATCHWORK_PATH_TOKEN . "\.zcache\.php'",
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
				echo '<script type="text/javascript">/*<![CDATA[*/scrollTo(0,0);if(window.parent&&parent.E&&parent.E.buffer.length)document.write(parent.E.buffer.join("")),parent.E.buffer=[]//]]></script>';
				break;
			}

			usleep($sleep);
		}

		exit;
	}

	static function filename($m)
	{
		return $GLOBALS['patchwork_path'][PATCHWORK_PATH_LEVEL - ((int)($m[3].$m[2]))]
			. str_replace('%1', '%', str_replace('%2', '_', strtr($m[1], '_', DIRECTORY_SEPARATOR)));
	}
}

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


function_exists('token_get_all') || die('Extension "tokenizer" is needed and not loaded.');
isset($_SERVER['REDIRECT_URL']) && die('C3MRO Init. Error: $_SERVER[\'REDIRECT_URL\'] must not be set at this stage.');

$CIA = realpath('.');

$appConfigSource = array();
$appInheritSeq = array();
$version_id = 0;

// Linearize application inheritance graph
$cia_paths = C3MRO(__CIA__, $CIA);
$cia_paths = array_slice($cia_paths, 1);
$cia_paths[] = __CIA__;

$cia_include_paths = explode(PATH_SEPARATOR, get_include_path());
$cia_include_paths = array_map('realpath', $cia_include_paths);
$cia_include_paths = array_diff($cia_include_paths, $cia_paths);
$cia_include_paths = array_merge($cia_paths, $cia_include_paths);
$cia_paths_offset  = count($cia_include_paths) - count($cia_paths) + 1;

$cia_zcache = false;

foreach ($cia_paths as $CIA)
{
	if (file_exists($CIA . '/zcache/'))
	{
		$cia_zcache = $CIA . '/zcache/';

		if (@touch($cia_zcache . 'write_test')) @unlink($cia_zcache . 'write_test');
		else $cia_zcache = false;

		break;
	}
}

if (!$cia_zcache)
{
	$cia_zcache = $cia_paths[0] . '/zcache/';
	file_exists($cia_zcache) || mkdir($cia_zcache);
}

$CIA = array(
	'#' . PHP_VERSION,
	'$cia_paths=' . var_export($cia_paths, true),
	'$cia_include_paths=' . var_export($cia_include_paths, true),
	'$cia_paths_offset='  . $cia_paths_offset,
	'$cia_zcache=' . var_export($cia_zcache, true),
	'$cia_private=false',
	'$cia_abstract=array()',
);


isset($_SERVER['REQUEST_TIME']) || $CIA[] = '$_SERVER[\'REQUEST_TIME\']=time()';

// {{{ IIS compatibility
isset($_SERVER['HTTPS']) && !isset($_SERVER['HTTPS_KEYSIZE']) && $CIA[] = 'unset($_SERVER[\'HTTPS\'])';
isset($_SERVER['QUERY_STRING']) || $CIA[] = '
$a = $_SERVER[\'REQUEST_URI\'];
$b = strpos($a, \'?\');
$_SERVER[\'QUERY_STRING\'] = false !== $b++ && $b < strlen($a) ? substr($a, $b) : \'\'';
// }}}

// {{{ Fix php.ini settings

// Disables mod_deflate who overwrites any custom Vary: header and appends a body to 304 responses.
// Replaced with our own output compression.
function_exists('apache_setenv')   && $CIA[] = 'apache_setenv(\'no-gzip\',\'1\')';
ini_get('zlib.output_compression') && $CIA[] = 'ini_set(\'zlib.output_compression\',false)';

if (extension_loaded('mbstring'))
{
	'none'  != mb_substitute_character() && $CIA[] = 'mb_substitute_character(\'none\')';
	'UTF-8' != mb_internal_encoding()    && $CIA[] = 'mb_internal_encoding(\'UTF-8\')';
	'pass'  != mb_http_output()          && $CIA[] = 'mb_http_output(\'pass\')';
	'uni'   != mb_language()             && $CIA[] = 'mb_language(\'uni\')';
}

if (function_exists('iconv'))
{
	'UTF-8' != iconv_get_encoding('input_encoding')    && $CIA[] = 'iconv_set_encoding(\'input_encoding\'   ,\'UTF-8\')';
	'UTF-8' != iconv_get_encoding('internal_encoding') && $CIA[] = 'iconv_set_encoding(\'internal_encoding\',\'UTF-8\')';
	'UTF-8' != iconv_get_encoding('output_encoding')   && $CIA[] = 'iconv_set_encoding(\'output_encoding\'  ,\'UTF-8\')';
}

get_magic_quotes_runtime() && $CIA[] = 'set_magic_quotes_runtime(false)';

# See http://www.w3.org/International/questions/qa-forms-utf-8
$a = !(extension_loaded('mbstring') && ini_get('mbstring.encoding_translation') && 'UTF-8' == ini_get('mbstring.http_input'));

if (get_magic_quotes_gpc() || $a) $CIA[] = '$a = array(&$_GET, &$_POST, &$_COOKIE);
foreach ($_FILES as &$v) $a[] = array(&$v[\'name\'], &$v[\'type\']);

$len = count($a);
for ($i = 0; $i < $len; ++$i)
{
	foreach ($a[$i] as &$v)
	{
		if (is_array($v)) $a[$len++] =& $v;
		else
		{
			' .
			(get_magic_quotes_gpc() ? '$v = ' . (ini_get('magic_quotes_sybase') ? 'str_replace("\'\'", "\'", ' : 'stripslashes(') . '$v);' : '' ) .
			( $a ? '!preg_match("\'\'u", $v) && preg_match_all(\'/(?:[\x00-\x7F]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2})+/\', $v, $b, PREG_PATTERN_ORDER) && $v = implode(\'\', $b[0]);unset($b);' : '') . '
		}
	}

	reset($a[$i]);
	unset($a[$i]);
} unset($a); unset($v)';
// }}}

// {{{ CIA's context early initialization
if (isset($_SERVER['CIA_BASE']))
{
	$a = false !== strpos($_SERVER['CIA_BASE'], '/__/');
	$a && $CIA[] = 'isset($_SERVER[\'CIA_BASE\']) || $_SERVER[\'CIA_BASE\'] = ' . var_export($_SERVER['CIA_BASE'], true);
	'/'  == substr($_SERVER['CIA_BASE'], 0, 1) && $CIA[] = '$_SERVER[\'CIA_BASE\'] = \'http\' . (isset($_SERVER[\'HTTPS\']) ? \'s\' : \'\') . \'://\' . $_SERVER[\'HTTP_HOST\'] . $_SERVER[\'CIA_BASE\']';
	$a && $CIA[] = 'if (isset($_SERVER[\'REDIRECT_URL\']))
{
	header(\'HTTP/1.1 200 OK\');
	$_SERVER[\'CIA_LANG\'] = \'\';
	$_SERVER[\'CIA_REQUEST\'] = substr($_SERVER[\'REDIRECT_URL\'], strlen(preg_replace("#^.*?://[^/]*#", \'\', $_SERVER[\'CIA_BASE\'])) - 3);
	if (isset($_SERVER[\'REDIRECT_QUERY_STRING\']))
	{
		$_SERVER[\'QUERY_STRING\'] = $_SERVER[\'REDIRECT_QUERY_STRING\'];
		parse_str($_SERVER[\'QUERY_STRING\'], $_GET);
	}
}';
}
else
{
	if (!(isset($_SERVER['PATH_INFO']) || isset($_SERVER['ORIG_PATH_INFO'])))
	{
		// Check if the webserver supports PATH_INFO

		$h = isset($_SERVER['HTTPS']) ? 'ssl' : 'tcp';
		$h = fsockopen("{$h}://{$_SERVER['SERVER_ADDR']}", $_SERVER['SERVER_PORT'], $errno, $errstr, 30);
		if (!$h) throw new Exception("Socket error n°{$errno}: {$errstr}");

		$a = strpos($_SERVER['REQUEST_URI'], '?');
		$a = false === $a ? $_SERVER['REQUEST_URI'] : substr($_SERVER['REQUEST_URI'], 0, $a);
		'/' == substr($a, -1) && $a .= 'index.php';

		$a  = "GET {$a}/_ HTTP/1.0\r\n";
		$a .= "Host: {$_SERVER['HTTP_HOST']}\r\n";
		$a .= "Connection: Close\r\n\r\n";

		fwrite($h, $a);
		$a = fgets($h, 12);
		fclose($h);

		strpos($a, ' 4') || $_SERVER['PATH_INFO'] = '';

		unset($a);
		unset($h);
	}

	$CIA[] = isset($_SERVER['PATH_INFO']) || isset($_SERVER['ORIG_PATH_INFO']) ? '#PATH_INFO enabled' : '#PATH_INFO disabled';
}
// }}}


$cia_paths_token = substr(md5(serialize($CIA)), 0, 4);

$lock = $cia_paths[0] . '/.' . $cia_paths_token . '.zcache.php';
if (!file_exists($lock) && $appInheritSeq = @fopen($lock . '.lock', 'xb'))
{
	fclose($appInheritSeq);
	array_map('unlink', glob('./.*.zcache.php', GLOB_NOSORT));
	rename($lock . '.lock', $lock);
}


$CIA[] = '$version_id=' . $version_id;
$CIA[] = '$cia_paths_token=\'' . $cia_paths_token . '\'';
$CIA[] = '$a' . $cia_paths_token . '=false';
$CIA[] = '$b' . $cia_paths_token . '=false';
$CIA[] = '$c' . $cia_paths_token . '=array()';
$CIA[] = '$cia_autoload_cache=&$c' . $cia_paths_token;

$CIA = array(implode(";\n", $CIA));

eval($CIA[0] . ';');


// Include config files

foreach ($cia_paths as $appInheritSeq) if (file_exists($appInheritSeq . '/config.cia.php'))
{
	$CIA[] = $appConfigSource[$appInheritSeq];
	require $appInheritSeq . '/config.cia.php';
}


$CIA = array(implode(";\n", $CIA));

$CIA[] = 'isset($CONFIG[\'P3P\'           ]) || $CONFIG[\'P3P\'] = \'CUR ADM\'';
$CIA[] = 'isset($CONFIG[\'session.cookie_path\'   ]) || $CONFIG[\'session.cookie_path\'] = \'/\'';
$CIA[] = 'isset($CONFIG[\'session.cookie_domain\' ]) || $CONFIG[\'session.cookie_domain\'] = \'\'';
$CIA[] = 'isset($CONFIG[\'DEBUG_ALLOWED\' ]) || $CONFIG[\'DEBUG_ALLOWED\'] = true';
$CIA[] = 'isset($CONFIG[\'DEBUG_PASSWORD\']) || $CONFIG[\'DEBUG_PASSWORD\'] = \'\'';
$CIA[] = 'isset($CONFIG[\'lang_list\'     ]) || $CONFIG[\'lang_list\'] = \'\'';
$CIA[] = '$cia_multilang = false !== strpos($CONFIG[\'lang_list\'], \'|\')';

// {{{ CIA's context initialization
/**
* Setup needed environment variables if they don't exists :
*   $_SERVER['CIA_BASE']: application's base part of the url. Lang independant (ex. /cia/myapp/__/)
*   $_SERVER['CIA_LANG']: lang (ex. en)
*   $_SERVER['CIA_REQUEST']: request part of the url (ex. myagent/mysubagent/...)
*
* You can also define these vars with mod_rewrite, to get cleaner URLs for example.
*/
if (!isset($_SERVER['CIA_BASE']))
{
	$CIA[] = '
$_SERVER[\'CIA_BASE\'] = \'http\' . (isset($_SERVER[\'HTTPS\']) ? \'s\' : \'\') . \'://\' . $_SERVER[\'HTTP_HOST\'] . $_SERVER[\'SCRIPT_NAME\'];
$_SERVER[\'CIA_LANG\'] = $_SERVER[\'CIA_REQUEST\'] = \'\'';

	if (isset($_SERVER['PATH_INFO']) || isset($_SERVER['ORIG_PATH_INFO']))
	{
		$CIA[] = 'isset($_SERVER[\'ORIG_PATH_INFO\']) && $_SERVER[\'PATH_INFO\'] = $_SERVER[\'ORIG_PATH_INFO\']';
		$CIA[] = 'isset($_SERVER[\'PATH_INFO\']) && $_SERVER[\'CIA_REQUEST\'] = substr($_SERVER[\'PATH_INFO\'], 1)';
		$CIA[] = '$_SERVER[\'CIA_BASE\'] .= \'/\' . ($cia_multilang ? \'__/\' : \'\')';
	}
	else
	{
		$CIA[] = '\'index.php\' == substr($_SERVER[\'CIA_BASE\'], -9) && $_SERVER[\'CIA_BASE\'] = substr($_SERVER[\'CIA_BASE\'], 0, -9)';
		$CIA[] = '$_SERVER[\'CIA_BASE\'] .= \'?\' . ($cia_multilang ? \'__/\' : \'\')';
		$CIA[] = '
$_SERVER[\'CIA_REQUEST\'] = $_SERVER[\'QUERY_STRING\'];

$a = strpos($_SERVER[\'QUERY_STRING\'], \'?\');
false !== $a || $a = strpos($_SERVER[\'QUERY_STRING\'], \'&\');

if (false !== $a)
{
	$_SERVER[\'CIA_REQUEST\'] = substr($_SERVER[\'QUERY_STRING\'], 0, $a);
	$_SERVER[\'QUERY_STRING\'] = substr($_SERVER[\'QUERY_STRING\'], $a+1);
	parse_str($_SERVER[\'QUERY_STRING\'], $_GET);
}
else if (\'\' !== $_SERVER[\'QUERY_STRING\'])
{
	$_SERVER[\'QUERY_STRING\'] = \'\';
	$a = key($_GET);
	unset($_GET[$a]);
	unset($_GET[$a]); // Double unset against a PHP security hole
}

$_SERVER[\'CIA_REQUEST\'] = urldecode($_SERVER[\'CIA_REQUEST\'])';
	}

	$CIA[] = 'if (preg_match("#^(" . $CONFIG[\'lang_list\'] . ")(?:/|$)(.*?)$#", $_SERVER[\'CIA_REQUEST\'], $a))
{
	$_SERVER[\'CIA_LANG\']    = $a[1];
	$_SERVER[\'CIA_REQUEST\'] = $a[2];
}';
}
// }}}

$CIA[] = '$cia_multilang || $_SERVER[\'CIA_LANG\'] = $CONFIG[\'lang_list\']';

$appConfigSource = '<?php ' . implode(";\n", $CIA) . ';';
cia_atomic_write($appConfigSource, '.config.zcache.php', $_SERVER['REQUEST_TIME'] + 1);

unset($CIA[0]);
$CIA && eval(implode(";\n", $CIA) . ';');


if (CIA_WINDOWS)
{
	$CIA = new COM('Scripting.FileSystemObject');
	$CIA->GetFile($lock)->Attributes |= 2; // Set hidden attribute
}

unset($appConfigSource);
unset($appInheritSeq);
unset($CIA);

// C3 Method Resolution Order (like in Python 2.3) for multiple application inheritance
// See http://python.org/2.3/mro.html
function C3MRO($appRealpath, $firstParent = false)
{
	$resultSeq =& $GLOBALS['appInheritSeq'][$appRealpath];

	// If result is cached, return it
	if (null !== $resultSeq) return $resultSeq;

	file_exists($appRealpath . '/config.cia.php') || die("Missing file: {$appRealpath}/config.cia.php");

	$GLOBALS['version_id'] += filemtime($appRealpath . '/config.cia.php');

	$parent = cia_get_parent_apps($appRealpath);

	// If no parent app, result is trival
	if (!$parent && !$firstParent) return array($appRealpath);

	if ($firstParent) array_unshift($parent, $firstParent);

	// Compute C3 MRO
	$seqs = array_merge(
		array(array($appRealpath)),
		array_map('C3MRO', $parent),
		array($parent)
	);
	$resultSeq = array();
	$parent = false;

	while (1)
	{
		if (!$seqs) return $resultSeq;

		unset($seq);
		$notHead = array();
		foreach ($seqs as $seq)
			foreach (array_slice($seq, 1) as $seq)
				$notHead[$seq] = 1;

		foreach ($seqs as &$seq)
		{
			$parent = reset($seq);

			if (isset($notHead[$parent])) $parent = false;
			else break;
		}

		if (!$parent) die("Inconsistent application hierarchy in {$appRealpath}/config.cia.php");

		$resultSeq[] = $parent;

		foreach ($seqs as $k => &$seq)
		{
			if ($parent == current($seq)) unset($seqs[$k][key($seq)]);
			if (!$seqs[$k]) unset($seqs[$k]);
		}
	}
}

function cia_get_parent_apps($appRealpath)
{
	// Get config's source and clean it
	$parent = file_get_contents($appRealpath . '/config.cia.php');
	if (false !== strpos($parent, "\r")) $parent = strtr(str_replace("\r\n", "\n", $parent), "\r", "\n");

	$token = token_get_all($parent);
	$parent = array();
	$source = array();
	$detectImport = true;

	foreach ($token as $token)
	{
		if (is_array($token))
		{
			$type = $token[0];
			$token = $token[1];
		}
		else $type = $token;

		switch ($type)
		{
		case T_OPEN_TAG: continue 2;

		case T_ECHO:
		case T_INLINE_HTML:
		case T_OPEN_TAG_WITH_ECHO:
			die('Error: echo detected in ' . htmlspecialchars($appRealpath) . '/config.cia.php');

		case T_CLOSE_TAG:
			$source[] = ';';
			continue 2;
		}

		if ($detectImport) switch ($type)
		{
			case T_COMMENT:
				$token = rtrim($token);
				if ('#' == $token[0] && preg_match('/^#import[ \t]/', $token)) $parent[] = substr($token, 8);

			case T_WHITESPACE:
			case T_DOC_COMMENT:
				break;

			default:
				$detectImport = false;
		}

		$source[] = $token;
	}

	$GLOBALS['appConfigSource'][$appRealpath] = implode('', $source);

	$len = count($parent);

	// Parent's config file path is relative to the current application's directory
	$k = 0;
	while ($k < $len)
	{
		$seq =& $parent[$k];

		$seq = trim($seq);
		if ('__CIA__' == substr($seq, 0, 7)) $seq = __CIA__ . substr($seq, 7);

		if ('/' != $seq[0] && '\\' != $seq[0] &&  ':' != $seq[1]) $seq = $appRealpath . '/' . $seq;

		if ('*' == substr($seq, -1) && $seq = realpath(substr($seq, 0, -1)))
		{
			if ($token = glob($seq . DIRECTORY_SEPARATOR . '*/config.cia.php', GLOB_NOSORT))
			{
				unset($seq);
				$type = array();

				foreach ($token as $token)
				{
					$token = substr($token, 0, -15);
					if (__CIA__ != $token)
					{
						foreach (C3MRO($token) as $seq)
						{
							if (false !== $seq = array_search($seq, $type))
							{
								$type[$seq] = $token;
								$token = false;
								break;
							}
						}

						$token && $type[] = $token;
					}
				}

				if ($token = count($type))
				{
					array_splice($parent, $k, 1, $type);

					$k += --$token;
					$len += $token;
				}
			}
			else unset($parent[$k]);
		}
		else
		{
			$seq = realpath($seq);
			if (__CIA__ == $seq) unset($parent[$k]);
		}

		++$k;
	}

	return $parent;
}

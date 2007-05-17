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


if ($lockHandle = @fopen('./.config.lock.php', 'xb'))
{
	flock($lockHandle, LOCK_EX);

	function patchwork_c3mro_die($message)
	{
		fclose($GLOBALS['lockHandle']);
		unlink('./.config.lock.php');
		die($message);
	}
}
else
{
	if ($lockHandle = @fopen('./.config.lock.php', 'rb'))
	{
		flock($lockHandle, LOCK_SH);
		fclose($lockHandle);
		$a = 300;
		while (--$a && !file_exists('./.config.patchwork.php')) sleep(1);
	}

	require './.config.patchwork.php';
	return;
}


$patchwork = realpath('.');

$appConfigSource = array();
$appInheritSeq = array();
$patchwork_appId = 0;

// Linearize application inheritance graph
$patchwork_paths = C3MRO(__patchwork__, $patchwork);
$patchwork_paths = array_slice($patchwork_paths, 1);
$patchwork_paths[] = __patchwork__;

$patchwork_include_paths = explode(PATH_SEPARATOR, get_include_path());
$patchwork_include_paths = array_map('realpath', $patchwork_include_paths);
$patchwork_include_paths = array_diff($patchwork_include_paths, $patchwork_paths, array(''));
$patchwork_include_paths = array_merge($patchwork_paths, $patchwork_include_paths);
$patchwork_paths_offset  = count($patchwork_include_paths) - count($patchwork_paths) + 1;

$patchwork_zcache = false;

foreach ($patchwork_paths as $patchwork)
{
	if (file_exists($patchwork . '/zcache/'))
	{
		$patchwork_zcache = $patchwork . '/zcache/';

		if (@touch($patchwork_zcache . 'write_test')) @unlink($patchwork_zcache . 'write_test');
		else $patchwork_zcache = false;

		break;
	}
}

if (!$patchwork_zcache)
{
	$patchwork_zcache = $patchwork_paths[0] . '/zcache/';
	file_exists($patchwork_zcache) || mkdir($patchwork_zcache);
}

$patchwork = array(
	'#' . PHP_VERSION,
	'$patchwork_paths=' . var_export($patchwork_paths, true),
	'$patchwork_include_paths=' . var_export($patchwork_include_paths, true),
	'$patchwork_paths_offset='  . $patchwork_paths_offset,
	'$patchwork_zcache=' . var_export($patchwork_zcache, true),
	'$patchwork_private=false',
	'$patchwork_abstract=array()',
);


isset($_SERVER['REQUEST_TIME']) || $patchwork[] = '$_SERVER[\'REQUEST_TIME\']=time()';

// {{{ IIS compatibility
isset($_SERVER['HTTPS']) && !isset($_SERVER['HTTPS_KEYSIZE']) && $patchwork[] = 'unset($_SERVER[\'HTTPS\'])';
isset($_SERVER['QUERY_STRING']) || $patchwork[] = '
$a = $_SERVER[\'REQUEST_URI\'];
$b = strpos($a, \'?\');
$_SERVER[\'QUERY_STRING\'] = false !== $b++ && $b < strlen($a) ? substr($a, $b) : \'\'';
// }}}

// {{{ Fix php.ini settings

// Disables mod_deflate who overwrites any custom Vary: header and appends a body to 304 responses.
// Replaced with our own output compression.
function_exists('apache_setenv')   && $patchwork[] = 'apache_setenv(\'no-gzip\',\'1\')';
ini_get('zlib.output_compression') && $patchwork[] = 'ini_set(\'zlib.output_compression\',false)';

if (extension_loaded('mbstring'))
{
	'none'  != mb_substitute_character() && $patchwork[] = 'mb_substitute_character(\'none\')';
	'UTF-8' != mb_internal_encoding()    && $patchwork[] = 'mb_internal_encoding(\'UTF-8\')';
	'pass'  != mb_http_output()          && $patchwork[] = 'mb_http_output(\'pass\')';
	'uni'   != mb_language()             && $patchwork[] = 'mb_language(\'uni\')';
}

if (function_exists('iconv'))
{
	'UTF-8' != iconv_get_encoding('input_encoding')    && $patchwork[] = 'iconv_set_encoding(\'input_encoding\'   ,\'UTF-8\')';
	'UTF-8' != iconv_get_encoding('internal_encoding') && $patchwork[] = 'iconv_set_encoding(\'internal_encoding\',\'UTF-8\')';
	'UTF-8' != iconv_get_encoding('output_encoding')   && $patchwork[] = 'iconv_set_encoding(\'output_encoding\'  ,\'UTF-8\')';
}

get_magic_quotes_runtime() && $patchwork[] = 'set_magic_quotes_runtime(false)';

# See http://www.w3.org/International/questions/qa-forms-utf-8
$a = !(extension_loaded('mbstring') && ini_get('mbstring.encoding_translation') && 'UTF-8' == ini_get('mbstring.http_input'));

if (get_magic_quotes_gpc() || $a) $patchwork[] = '$a = array(&$_GET, &$_POST, &$_COOKIE);
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

// {{{ patchwork's context early initialization
if (isset($_SERVER['PATCHWORK_BASE']))
{
	$a = false !== strpos($_SERVER['PATCHWORK_BASE'], '/__/');
	$a && $patchwork[] = 'isset($_SERVER[\'PATCHWORK_BASE\']) || $_SERVER[\'PATCHWORK_BASE\'] = ' . var_export($_SERVER['PATCHWORK_BASE'], true);
	'/'  == substr($_SERVER['PATCHWORK_BASE'], 0, 1) && $patchwork[] = '$_SERVER[\'PATCHWORK_BASE\'] = \'http\' . (isset($_SERVER[\'HTTPS\']) ? \'s\' : \'\') . \'://\' . $_SERVER[\'HTTP_HOST\'] . $_SERVER[\'PATCHWORK_BASE\']';
	$a && $patchwork[] = 'if (isset($_SERVER[\'REDIRECT_URL\']))
{
	header(\'HTTP/1.1 200 OK\');
	$_SERVER[\'PATCHWORK_LANG\'] = \'\';
	$_SERVER[\'PATCHWORK_REQUEST\'] = substr($_SERVER[\'REDIRECT_URL\'], strlen(preg_replace("#^.*?://[^/]*#", \'\', $_SERVER[\'PATCHWORK_BASE\'])) - 3);
	if (isset($_SERVER[\'REDIRECT_QUERY_STRING\']))
	{
		$_SERVER[\'QUERY_STRING\'] = $_SERVER[\'REDIRECT_QUERY_STRING\'];
		parse_str($_SERVER[\'QUERY_STRING\'], $_GET);
	}
}';
}
else
{
	if (!isset($_SERVER['PATH_INFO']))
	{
		// Check if the webserver supports PATH_INFO

		$h = isset($_SERVER['HTTPS']) ? 'ssl' : 'tcp';
		$h = fsockopen("{$h}://{$_SERVER['SERVER_ADDR']}", $_SERVER['SERVER_PORT'], $errno, $errstr, 30);
		if (!$h) throw new Exception("Socket error n°{$errno}: {$errstr}");

		$a = strpos($_SERVER['REQUEST_URI'], '?');
		$a = false === $a ? $_SERVER['REQUEST_URI'] : substr($_SERVER['REQUEST_URI'], 0, $a);
		'/' == substr($a, -1) && $a .= 'index.php';

		$a  = "GET {$a}/_?exit$ HTTP/1.0\r\n";
		$a .= "Host: {$_SERVER['HTTP_HOST']}\r\n";
		$a .= "Connection: Close\r\n\r\n";

		fwrite($h, $a);
		$a = fgets($h, 12);
		fclose($h);

		strpos($a, ' 4') || $_SERVER['PATH_INFO'] = '';

		unset($a);
		unset($h);
	}

	$patchwork[] = isset($_SERVER['PATH_INFO']) ? '#PATH_INFO enabled' : '#PATH_INFO disabled';
}
// }}}


$patchwork_paths_token = substr(md5(serialize($patchwork)), 0, 4);

$a = $patchwork_paths[0] . '/.' . $patchwork_paths_token . '.zcache.php';
if (!file_exists($a))
{
	@array_map('unlink', glob('./.*.zcache.php', GLOB_NOSORT));
	touch($a);
	if (IS_WINDOWS)
	{
		$h = new COM('Scripting.FileSystemObject');
		$h->GetFile($a)->Attributes |= 2; // Set hidden attribute
		unset($h);
	}
}


$patchwork[] = '$patchwork_appId=' . $patchwork_appId;
$patchwork[] = '$patchwork_paths_token=\'' . $patchwork_paths_token . '\'';
$patchwork[] = '$a' . $patchwork_paths_token . '=false';
$patchwork[] = '$b' . $patchwork_paths_token . '=false';
$patchwork[] = '$c' . $patchwork_paths_token . '=array()';
$patchwork[] = '$patchwork_autoload_cache=&$c' . $patchwork_paths_token;

$patchwork = array(implode(";\n", $patchwork));

eval($patchwork[0] . ';');


// Include config files

foreach ($patchwork_paths as $appInheritSeq) if (file_exists($appInheritSeq . '/config.patchwork.php'))
{
	$patchwork[] = $appConfigSource[$appInheritSeq];
	require $appInheritSeq . '/config.patchwork.php';
}


$patchwork = array(implode(";\n", $patchwork));


$h = ini_get('max_execution_time');
set_time_limit(0);
$appConfigSource = count($patchwork_paths);
foreach ($patchwork_include_paths as $a => $appInheritSeq)
{
	@patchwork_populatePathCache($patchwork_zcache, $appInheritSeq, $a, $a < $appConfigSource ? '' : '/class');
}
set_time_limit($h);

$patchwork[] = 'isset($CONFIG[\'session.cookie_path\'  ]) || $CONFIG[\'session.cookie_path\'] = \'/\'';
$patchwork[] = 'isset($CONFIG[\'session.cookie_domain\']) || $CONFIG[\'session.cookie_domain\'] = \'\'';
$patchwork[] = 'isset($CONFIG[\'P3P\'           ]) || $CONFIG[\'P3P\'] = \'CUR ADM\'';
$patchwork[] = 'isset($CONFIG[\'DEBUG_ALLOWED\' ]) || $CONFIG[\'DEBUG_ALLOWED\'] = true';
$patchwork[] = 'isset($CONFIG[\'DEBUG_PASSWORD\']) || $CONFIG[\'DEBUG_PASSWORD\'] = \'\'';
$patchwork[] = 'isset($CONFIG[\'lang_list\'     ]) || $CONFIG[\'lang_list\'] = \'\'';
$patchwork[] = '$patchwork_multilang = false !== strpos($CONFIG[\'lang_list\'], \'|\')';

// {{{ patchwork's context initialization
/**
* Setup needed environment variables if they don't exists :
*   $_SERVER['PATCHWORK_BASE']: application's base part of the url. Lang independant (ex. /myapp/__/)
*   $_SERVER['PATCHWORK_LANG']: lang (ex. en)
*   $_SERVER['PATCHWORK_REQUEST']: request part of the url (ex. myagent/mysubagent/...)
*
* You can also define these vars with mod_rewrite, to get cleaner URLs for example.
*/
if (!isset($_SERVER['PATCHWORK_BASE']))
{
	$patchwork[] = '
$_SERVER[\'PATCHWORK_BASE\'] = \'http\' . (isset($_SERVER[\'HTTPS\']) ? \'s\' : \'\') . \'://\' . $_SERVER[\'HTTP_HOST\'] . $_SERVER[\'SCRIPT_NAME\'];
$_SERVER[\'PATCHWORK_LANG\'] = $_SERVER[\'PATCHWORK_REQUEST\'] = \'\'';

	if (isset($_SERVER['PATH_INFO']))
	{
		$patchwork[] = 'isset($_SERVER[\'PATH_INFO\']) && $_SERVER[\'PATCHWORK_REQUEST\'] = substr($_SERVER[\'PATH_INFO\'], 1)';
		$patchwork[] = '$_SERVER[\'PATCHWORK_BASE\'] .= \'/\' . ($patchwork_multilang ? \'__/\' : \'\')';
	}
	else
	{
		$patchwork[] = '\'index.php\' == substr($_SERVER[\'PATCHWORK_BASE\'], -9) && $_SERVER[\'PATCHWORK_BASE\'] = substr($_SERVER[\'PATCHWORK_BASE\'], 0, -9)';
		$patchwork[] = '$_SERVER[\'PATCHWORK_BASE\'] .= \'?\' . ($patchwork_multilang ? \'__/\' : \'\')';
		$patchwork[] = '
$_SERVER[\'PATCHWORK_REQUEST\'] = $_SERVER[\'QUERY_STRING\'];

$a = strpos($_SERVER[\'QUERY_STRING\'], \'?\');
false !== $a || $a = strpos($_SERVER[\'QUERY_STRING\'], \'&\');

if (false !== $a)
{
	$_SERVER[\'PATCHWORK_REQUEST\'] = substr($_SERVER[\'QUERY_STRING\'], 0, $a);
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

$_SERVER[\'PATCHWORK_REQUEST\'] = urldecode($_SERVER[\'PATCHWORK_REQUEST\'])';
	}

	$patchwork[] = 'if (preg_match("#^(" . $CONFIG[\'lang_list\'] . ")(?:/|$)(.*?)$#", $_SERVER[\'PATCHWORK_REQUEST\'], $a))
{
	$_SERVER[\'PATCHWORK_LANG\']    = $a[1];
	$_SERVER[\'PATCHWORK_REQUEST\'] = $a[2];
}';
}
// }}}

$patchwork[] = '$patchwork_multilang || $_SERVER[\'PATCHWORK_LANG\'] = $CONFIG[\'lang_list\']';

$appConfigSource = '<?php ' . implode(";\n", $patchwork) . ';';
fwrite($lockHandle, $appConfigSource);
fclose($lockHandle);

touch('./.config.lock.php', $_SERVER['REQUEST_TIME'] + 1);

if (IS_WINDOWS)
{
	$h = new COM('Scripting.FileSystemObject');
	$h->GetFile(PATCHWORK_PROJECT_PATH . '/.config.lock.php')->Attributes |= 2; // Set hidden attribute
}

rename('./.config.lock.php', './.config.patchwork.php');


unset($patchwork[0]);
$patchwork && eval(implode(";\n", $patchwork) . ';');


unset($appConfigSource);
unset($appInheritSeq);
unset($patchwork);
unset($h);

// C3 Method Resolution Order (like in Python 2.3) for multiple application inheritance
// See http://python.org/2.3/mro.html
function C3MRO($appRealpath, $firstParent = false)
{
	$resultSeq =& $GLOBALS['appInheritSeq'][$appRealpath];

	// If result is cached, return it
	if (null !== $resultSeq) return $resultSeq;

	file_exists($appRealpath . '/config.patchwork.php') || patchwork_c3mro_die("Missing file: {$appRealpath}/config.patchwork.php");

	$GLOBALS['patchwork_appId'] += filemtime($appRealpath . '/config.patchwork.php');

	$parent = patchwork_get_parent_apps($appRealpath);

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

		if (!$parent) patchwork_c3mro_die("Inconsistent application hierarchy in {$appRealpath}/config.patchwork.php");

		$resultSeq[] = $parent;

		foreach ($seqs as $k => &$seq)
		{
			if ($parent == current($seq)) unset($seqs[$k][key($seq)]);
			if (!$seqs[$k]) unset($seqs[$k]);
		}
	}
}

function patchwork_get_parent_apps($appRealpath)
{
	// Get config's source and clean it
	$parent = file_get_contents($appRealpath . '/config.patchwork.php');
	if (false !== strpos($parent, "\r")) $parent = strtr(str_replace("\r\n", "\n", $parent), "\r", "\n");

	$token = token_get_all($parent);
	$parent = array();
	$source = array();
	$detectImport = true;
	$bracket = 0;

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
			$bracket || patchwork_c3mro_die("Error: echo detected in {$appRealpath}/config.patchwork.php");
			break;

		case T_CLOSE_TAG:
			$source[] = ';';
			continue 2;

		case T_FUNCTION:
			$bracket = 1;
			break;

		case '{':
			$bracket && ++$bracket;
			break;

		case '}':
			     $bracket && --$bracket;
			1 == $bracket && --$bracket;
			break;

		case ';':
			1 == $bracket && --$bracket;
			break;
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
		if ('__patchwork__' == substr($seq, 0, 13)) $seq = __patchwork__ . substr($seq, 13);

		if ('/' != $seq[0] && '\\' != $seq[0] &&  ':' != $seq[1]) $seq = $appRealpath . '/' . $seq;

		if ('*' == substr($seq, -1) && $seq = realpath(substr($seq, 0, -1)))
		{
			$token = glob($seq . DIRECTORY_SEPARATOR . '**/config.patchwork.php', GLOB_NOSORT);

			$type = array();
			file_exists($seq . '/config.patchwork.php') && $type[] = $seq;
			unset($seq);

			foreach ($token as $token)
			{
				$token = substr($token, 0, -21);
				if (__patchwork__ != $token)
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

			$token = count($type);

			array_splice($parent, $k, 1, $type);

			$k += --$token;
			$len += $token;
		}
		else
		{
			$seq = realpath($seq);
			if (__patchwork__ == $seq) unset($parent[$k]);
		}

		++$k;
	}

	return $parent;
}

function patchwork_populatePathCache(&$patchwork_zcache, $dir, $i, $prefix, $subdir = '/')
{
	if ($h = opendir($dir . $subdir))
	{
		if ('/' != $subdir && file_exists($dir . $subdir . 'config.patchwork.php')) ;
		else while (false !== $file = readdir($h)) if ('.' != $file[0] && 'zcache' != $file)
		{
			$file = $subdir . $file;

			$cache = substr($prefix . $file, 1);
			$cache = md5($cache);
			$cache = $cache[0] . '/' . $cache[1] . '/' . substr($cache, 2) . '.cachePath.txt';

			if (false === $f = fopen($patchwork_zcache . $cache, 'ab'))
			{
				@mkdir($patchwork_zcache . $cache[0]);
				@mkdir($patchwork_zcache . substr($cache, 0, 3));
				$f = fopen($patchwork_zcache . $cache, 'ab');
			}

			fwrite($f, $i . ',');
			fclose($f);

			patchwork_populatePathCache($patchwork_zcache, $dir, $i, $prefix, $file . '/');
		}

		closedir($h);
	}
}

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


$version_id = realpath('.');

$appConfigSource = array();
$appInheritSeq = array();

$CIA = $version_id;
$version_id = 0;

// Linearize application inheritance graph
$cia_paths = C3MRO($CIA);

$cia_include_paths = explode(PATH_SEPARATOR, get_include_path());
$cia_include_paths = array_map('realpath', $cia_include_paths);
$cia_include_paths = array_diff($cia_include_paths, $cia_paths);
$cia_include_paths = array_merge($cia_paths, $cia_include_paths);
$cia_paths_offset  = count($cia_include_paths) - count($cia_paths) + 1;

$CIA = array(
	'#' . PHP_VERSION,
	'$cia_paths=' . var_export($cia_paths, true),
	'$cia_include_paths=' . var_export($cia_include_paths, true),
	'$cia_paths_offset='  . $cia_paths_offset,
	'$cia_abstract=(object)array();',
);


isset($_SERVER['REQUEST_TIME']) || $CIA[] = '$_SERVER[\'REQUEST_TIME\']=time()';


// {{{ Fix php.ini settings

// Disables mod_deflate who overwrites any custom Vary: header and appends a body to 304 responses.
// Replaced with our own output compression.
function_exists('apache_setenv')   && $CIA[] = 'apache_setenv(\'no-gzip\',\'1\')';
ini_get('zlib.output_compression') && $CIA[] = 'ini_set(\'zlib.output_compression\',false)';

extension_loaded('mbstring') && 'UTF-8' != mb_internal_encoding() && $CIA[] = 'mb_internal_encoding(\'UTF-8\')';

if (function_exists('iconv'))
{
	'UTF-8' != iconv_get_encoding('input_encoding')    && $CIA[] = 'iconv_set_encoding(\'input_encoding\'   ,\'UTF-8\')';
	'UTF-8' != iconv_get_encoding('internal_encoding') && $CIA[] = 'iconv_set_encoding(\'internal_encoding\',\'UTF-8\')';
	'UTF-8' != iconv_get_encoding('output_encoding')   && $CIA[] = 'iconv_set_encoding(\'output_encoding\'  ,\'UTF-8\')';
}

get_magic_quotes_runtime() && $CIA[] = 'set_magic_quotes_runtime(false)';

if (get_magic_quotes_gpc())
{
	$CIA[] = ini_get('magic_quotes_sybase')
		? 'function _q_(&$a) {static $d=999; --$d&&is_array($a) ? array_walk($a, \'_q_\') : $a = str_replace("\'\'", "\'", $a); ++$d;} }'
		: 'function _q_(&$a) {static $d=999; --$d&&is_array($a) ? array_walk($a, \'_q_\') : $a = stripslashes($a); ++$d;} }';
	$CIA[] = '_q_($_GET); _q_($_POST); _q_($_COOKIE)';
}

if (
	   !(extension_loaded('mbstring')
	&& ini_get('mbstring.encoding_translation')
	&& 'UTF-8' == ini_get('mbstring.http_input'))
) $CIA[] = '
function _u_(&$a)
{
	# See http://www.w3.org/International/questions/qa-forms-utf-8

	static $d=999, $rx = \'/(?:[\x00-\x7F]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2})+/\';

	if (--$d && is_array($a)) array_walk($a, \'_u_\');
	else if (!preg_match("\'\'u", $a))
	{
		preg_match_all($rx, $a, $a, PREG_PATTERN_ORDER);
		$a = implode(\'\', $a[0]);
	}

	++$d;
}
# $_GET has already been fixed together with $_SERVER[\'REQUEST_URI\']
_u_($_POST); _u_($_COOKIE); _u_($_FILES)';
// }}}

// {{{ CIA's environment context
/**
* Setup needed environment variables if they don't exists :
*   $_SERVER['CIA_HOME']: application's home part of the url. Lang independant (ex. /cia/myapp/__/)
*   $_SERVER['CIA_LANG']: lang (ex. en)
*   $_SERVER['CIA_REQUEST']: request part of the url (ex. myagent/mysubagent/...)
*
* You can also define these vars with mod_rewrite, to get cleaner URLs for example.
*/
if (!isset($_SERVER['CIA_HOME']))
{
		$CIA[] = '
$_SERVER[\'CIA_HOME\'] = \'http\' . (isset($_SERVER[\'HTTPS\']) ? \'s\' : \'\') . \'://\' . $_SERVER[\'HTTP_HOST\'] . $_SERVER[\'SCRIPT_NAME\'];
$_SERVER[\'CIA_LANG\'] = $_SERVER[\'CIA_REQUEST\'] = \'\';

$lang_rx = \'([a-z]{2}(?:-[A-Z]{2})?)\'';

		if (!(isset($_SERVER['PATH_INFO']) || isset($_SERVER['ORIG_PATH_INFO'])))
		{
			// Check if the webserver supports PATH_INFO

			$host = $_SERVER['HTTP_HOST'];

			if (preg_match("':([^0-9]*)$'", $host, $h)) $host = (isset($_SERVER['HTTPS']) ? 'ssl://' : 'tcp://') . $host . (''===$h[1] ? '' : '80');
			else if (isset($_SERVER['HTTPS'])) $host = 'ssl://' . $host . ':443';
			else $host = 'tcp://' . $host . ':80';

			$h = stream_socket_client($host, $errno, $errstr, 5);
			if (!$h) throw new Exception("Socket error n°{$errno}: {$errstr}");

			$host  = "GET {$_SERVER['SCRIPT_NAME']}/ HTTP/1.1\r\n";
			$host .= "Host: {$_SERVER['HTTP_HOST']}\r\n";
			$host .= "Connection: Close\r\n\r\n";

			fwrite($h, $host);
			$host = fgets($h, 14);
			fclose($h);

			if (false !== strpos($host, '200')) $_SERVER['PATH_INFO'] = '';

			unset($host);
			unset($h);
		}

		$appInheritSeq = isset($_SERVER['ORIG_PATH_INFO']) ? '$_SERVER[\'PATH_INFO\'] = $_SERVER[\'ORIG_PATH_INFO\'];' : '';

		$CIA[] = isset($_SERVER['PATH_INFO']) || isset($_SERVER['ORIG_PATH_INFO'])
			? $appInheritSeq . '
$_SERVER[\'CIA_HOME\'] .= \'/__/\';

if (isset($_SERVER[\'PATH_INFO\']) && preg_match("\'^/{$lang_rx}/?(.*)$\'", $_SERVER[\'PATH_INFO\'], $a))
{
	$_SERVER[\'CIA_LANG\']    = $a[1];
	$_SERVER[\'CIA_REQUEST\'] = $a[2];
}'
			: '
$_SERVER[\'CIA_HOME\'] .= \'?__/\';

if (isset($_SERVER[\'QUERY_STRING\']) && preg_match("\'^{$lang_rx}/?(.*?)(?:[\?&](.*))?$\'", rawurldecode($_SERVER[\'QUERY_STRING\']), $a))
{
	$_SERVER[\'CIA_LANG\']    = $a[1];
	$_SERVER[\'CIA_REQUEST\'] = $a[2];

	if (isset($a[3]))
	{
		$_GET = array();
		$_SERVER[\'QUERY_STRING\'] = $a[3];
		parse_str($_SERVER[\'QUERY_STRING\'], $_GET);
	}
	else
	{
		$_SERVER[\'QUERY_STRING\'] = null;
		$a = key($_GET);
		unset($_GET[$a]);
		unset($_GET[$a]); // Double unset against a PHP security hole
	}
}';
}
else if (!strncmp('/', $_SERVER['CIA_HOME'], 1)) $CIA[] = '$_SERVER[\'CIA_HOME\'] = \'http\' . (isset($_SERVER[\'HTTPS\']) && $_SERVER[\'HTTPS\'] ? \'s\' : \'\') . \'://\' . $_SERVER[\'HTTP_HOST\'] . $_SERVER[\'CIA_HOME\']';
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
$CIA[] = '$c' . $cia_paths_token . '=(object)array()';
$CIA[] = '$cia_autoload_cache=&$c' . $cia_paths_token;

$CIA = array(implode(";\n", $CIA));

eval($CIA[0] . ';');

foreach ($cia_paths as $appInheritSeq)
{
	require $appInheritSeq . '/config.php';
	$CIA[] = $appConfigSource[$appInheritSeq];
}

$CIA = '<?php ' . implode(";\n", $CIA) . ';';
cia_atomic_write($CIA, '.config.zcache.php');

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
function C3MRO($appRealpath)
{
	$resultSeq =& $GLOBALS['appInheritSeq'][$appRealpath];

	// If result is cached, return it
	if (null !== $resultSeq) return $resultSeq;

	if (!file_exists($appRealpath . '/config.php')) die('Missing file config.php in ' . htmlspecialchars($appRealpath));

	$GLOBALS['version_id'] += filemtime($appRealpath . '/config.php');

	// Get config's source and clean it
	$parent = file_get_contents($appRealpath . '/config.php');
	if (false !== strpos($parent, "\r")) $parent = strtr(str_replace("\r\n", "\n", $parent), "\r", "\n");

	$k = false;

	if ('<?' == substr($parent, 0, 2))
	{
		$seq = preg_replace("'^<\?(?:php)?\s'i", '', $parent);
		$k = $seq != $parent;
		$parent = trim($seq);
		if ('?>' == substr($parent, -2)) $parent = substr($parent, 0, -2) . ';';
	}
	else
	{
		$seq = preg_replace("#^<script\s+language\s*=\s*(|[\"'])php\1\s*>#i", '', $parent);
		$k = $seq != $parent;
		$parent = trim($seq);
		$parent = preg_replace("'</script\s*>$'i", ';', $parent);
	}

	if (!$k) die('Failed to detect PHP open tag (<?php) at the beginning of ' . htmlspecialchars($appRealpath) . '/config.php');

	$GLOBALS['appConfigSource'][$appRealpath] = $parent;

	// Get parent application(s)
	if (preg_match("'^#extends[ \t].+(?:\n#.+)*'i", $parent, $parent))
	{
		$parent = '#' . substr($parent[0], 9);
		preg_match_all("'^#(.+?)$'m", $parent, $parent);
		$parent = $parent[1];
	}
	else $parent = false;

	if (__CIA__ == $appRealpath && $parent) die('#extends clause is forbidden in root config file: ' . htmlspecialchars(__CIA__) . '/config.php');

	// If no parent app, result is trival
	if (!$parent) return array($appRealpath);

	$resultSeq = count($parent);

	// Parent's config file path is relative to the current application's directory
	$k = 0;
	while ($k < $resultSeq)
	{
		$seq =& $parent[$k];

		$seq = trim($seq);
		if ('__CIA__' == substr($seq, 0, 7)) $seq = __CIA__ . substr($seq, 7);

		if ('/' != $seq[0] && '\\' != $seq[0] &&  ':' != $seq[1]) $seq = $appRealpath . '/' . $seq;

		$seq = realpath($seq);

		++$k;
	}

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

		foreach ($seqs as &$seq)
		{
			$parent = reset($seq);

			unset($seq);

			foreach ($seqs as $seq)
			{
				unset($seq[key($seq)]);

				if (in_array($parent, $seq))
				{
					$parent = false;
					break;
				}
			}

			if ($parent) break;
		}

		if (!$parent) die('Inconsistent application hierarchy');

		$resultSeq[] = $parent;

		foreach ($seqs as $k => &$seq)
		{
			if ($parent == current($seq)) unset($seq[key($seq)]);
			if (!$seq) unset($seqs[$k]);
		}
	}
}

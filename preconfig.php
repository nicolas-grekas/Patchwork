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



/**** Pre-configuration stage 0 ****/



/*<*/PHP_VERSION/*>*/;

define('patchwork', microtime(true));
error_reporting(E_ALL | E_STRICT);
isset($_SERVER['HTTP_HOST']) && strspn($_SERVER['HTTP_HOST'], '\'"<>') && die('Invalid HTTP/1.1 Host header');

define('PATCHWORK_PROJECT_PATH', /*<*/__patchwork_bootstrapper::$cwd   /*>*/);
define('PATCHWORK_ZCACHE',       /*<*/__patchwork_bootstrapper::$zcache/*>*/);
define('PATCHWORK_PATH_LEVEL',   /*<*/__patchwork_bootstrapper::$last  /*>*/);
define('PATCHWORK_PATH_OFFSET',  /*<*/__patchwork_bootstrapper::$offset/*>*/);

$_REQUEST = array(); // $_REQUEST is an open door to security problems.
$CONFIG   = array();

define('IS_WINDOWS', /*<*/'\\' === DIRECTORY_SEPARATOR/*>*/);
define('IS_POSTING', 'POST' === $_SERVER['REQUEST_METHOD']);


$patchwork_path = /*<*/$patchwork_path/*>*/;
$patchwork_abstract = array();


/*#>*/if (!isset($_SERVER['REQUEST_TIME']))
		$_SERVER['REQUEST_TIME'] = time();


// Fix some $_SERVER variables under Windows

/*#>*/if ('\\' === DIRECTORY_SEPARATOR)
/*#>*/{
/*#>*/	if (version_compare(PHP_VERSION, '5.2.6', '<')) // Fix for http://bugs.php.net/bug.php?id=44001
/*#>*/	{
			if (isset($_SERVER['PATH_INFO'])
				&& '.' === substr($_SERVER['SCRIPT_NAME'], -1)
				&& '.' !== substr($_SERVER['PATH_INFO'], -1)
				&& (false !== strpos($_SERVER['REQUEST_URI'], '?') ? false !== strpos($_SERVER['REQUEST_URI'], '.?') : '.' === substr($_SERVER['REQUEST_URI'], -1))
			)
			{
				$a = strlen($_SERVER['SCRIPT_NAME']) - 1;
				$b = 1;
				while ('.' === substr($_SERVER['SCRIPT_NAME'], $a - $b, 1)) ++$b;

				$a = substr($_SERVER['SCRIPT_NAME'], -$b);

				$_SERVER['PATH_INFO'] .= $a;
				$_SERVER['PATH_TRANSLATED'] .= $a;
				$_SERVER['SCRIPT_NAME'] = substr($_SERVER['SCRIPT_NAME'], 0, -strlen($_SERVER['PATH_INFO']));
			}
/*#>*/	}

		// IIS compatibility

/*#>*/	if (!isset($_SERVER['REQUEST_URI']))
			$_SERVER['REQUEST_URI'] = isset($_SERVER['HTTP_X_REWRITE_URL']) ? $_SERVER['HTTP_X_REWRITE_URL'] : $_SERVER['URL'];

/*#>*/	if (!isset($_SERVER['SERVER_ADDR']))
			$_SERVER['SERVER_ADDR'] = '127.0.0.1';

/*#>*/	if (!isset($_SERVER['QUERY_STRING']))
/*#>*/	{
			$a = $_SERVER['REQUEST_URI'];
			$b = strpos($a, '?');
			$_SERVER['QUERY_STRING'] = false !== $b++ && $b < strlen($a) ? substr($a, $b) : '';
/*#>*/	}
/*#>*/}


if (isset($_SERVER['HTTPS']))
{
	if ('on' === strtolower($_SERVER['HTTPS']) || '1' == $_SERVER['HTTPS']) $_SERVER['HTTPS'] = 'on';
	else unset($_SERVER['HTTPS']);
}

if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) // Is it problematic to trust this header ?
{
	if ('https' === strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'])) $_SERVER['HTTPS'] = 'on';
	else unset($_SERVER['HTTPS']);

	$a = strstr($_SERVER['HTTP_HOST'], ':');
	$_SERVER['SERVER_PORT'] = false !== $a ? (string)(int) substr($a, 1) : (isset($_SERVER['HTTPS']) ? '443' : '80');
	$_SERVER['SERVER_ADDR'] = $_SERVER['REMOTE_ADDR'];
}


// Utility functions

function patchwork_include($file)     {global $CONFIG; return include $file;}
function patchwork_is_a($obj, $class) {return $obj instanceof $class;}
function lowerascii($s) {return strtr($s, 'CLASPEMITDBFRUGNJVHOWKXQYZ', 'claspemitdbfrugnjvhowkxqyz');}

/*#>*/if (function_exists('getcwd') && @getcwd())
function patchwork_chdir($realdir) {rtrim($realdir, /*<*/DIRECTORY_SEPARATOR/*>*/) === rtrim(getcwd(), /*<*/DIRECTORY_SEPARATOR/*>*/) || chdir($realdir);}
/*#>*/else
function patchwork_chdir($realdir) {chdir($realdir);}

register_shutdown_function('patchwork_chdir', /*<*/__patchwork_bootstrapper::$cwd/*>*/);


// registerAutoloadPrefix()

$patchwork_autoload_prefix = array();

function registerAutoloadPrefix($class_prefix, $class_to_file_callback)
{
	if ($len = strlen($class_prefix))
	{
		$registry =& $GLOBALS['patchwork_autoload_prefix'];
		$class_prefix = lowerascii($class_prefix);
		$i = 0;

		do
		{
			$c = ord($class_prefix[$i]);
			isset($registry[$c]) || $registry[$c] = array();
			$registry =& $registry[$c];
		}
		while (++$i < $len);

		$registry[-1] = $class_to_file_callback;
	}
}


// patchwork-specific include_path-like mechanism

function resolvePath($file, $level = false, $base = false)
{
	global $patchwork_lastpath_level;

/*#>*/	if ('\\' === DIRECTORY_SEPARATOR)
		false !== strpos($file, '\\') && $file = strtr($file, '\\', '/');

	if (false === $level)
	{
		if (false !== strpos('.' . $file, './') || /*<*/'\\' === DIRECTORY_SEPARATOR/*>*/ && ':/' === substr($file, 1, 2))
		{
			$patchwork_lastpath_level = -/*<*/__patchwork_bootstrapper::$offset/*>*/;

			if ($f = realpath($file)) $file = $f;

			$p =& $GLOBALS['patchwork_path'];

			for ($i = 0; $i < /*<*/count($patchwork_path)/*>*/; ++$i)
			{
				if (substr($file, 0, strlen($p[$i])) === $p[$i])
				{
					$file = substr($file, strlen($p[$i]));
					if (/*<*/__patchwork_bootstrapper::$last/*>*/ < $i) $file = 'class/' . $file;
					break;
				}
			}

			if (/*<*/count($patchwork_path)/*>*/ === $i) return $f;
		}

		$i = 0;
		$level = /*<*/__patchwork_bootstrapper::$last/*>*/;
	}
	else
	{
		0 <= $level && $base = 0;
		$i = /*<*/__patchwork_bootstrapper::$last/*>*/ - $level - $base;
		0 > $i && $i = 0;
	}

	$patchwork_lastpath_level = $level;

	if (0 == $i)
	{
		$source = /*<*/__patchwork_bootstrapper::$cwd/*>*/ . $file;

/*#>*/	if ('\\' === DIRECTORY_SEPARATOR)
/*#>*/	{
			if (function_exists('win_file_exists') ? win_file_exists($source) : file_exists($source)) return $source;
/*#>*/	}
/*#>*/	else
/*#>*/	{
			if (file_exists($source)) return $source;
/*#>*/	}
	}


	if ($slash = '/' === substr($file, -1)) $file = substr($file, 0, -1);


/*#>*/if ($a = __patchwork_bootstrapper::buildPathCache())
/*#>*/{
		static $db;
		isset($db) || $db = dba_popen(/*<*/__patchwork_bootstrapper::$cwd . '.parentPaths.db'/*>*/, 'rd', /*<*/$a/*>*/);
		$base = dba_fetch($file, $db);
/*#>*/}
/*#>*/else
/*#>*/{
		$base = md5($file);
		$base = /*<*/__patchwork_bootstrapper::$zcache/*>*/ . $base[0] . '/' . $base[1] . '/' . substr($base, 2) . '.path.txt';
		$base = @file_get_contents($base);
/*#>*/}

	if (false !== $base)
	{
		$base = explode(',', $base);
		do if (current($base) >= $i)
		{
			$base = (int) current($base);
			$level = $patchwork_lastpath_level -= $base - $i;

			return $GLOBALS['patchwork_path'][$base] . (0<=$level ? $file : substr($file, 6)) . ($slash ? '/' : '');
		}
		while (false !== next($base));
	}

	$patchwork_lastpath_level = -/*<*/__patchwork_bootstrapper::$offset/*>*/;

	return false;
}


// Class hunter: a user callback is called when a hunter object is destroyed

class hunter
{
	protected

	$callback,
	$param_arr;


	function __construct($callback, $param_arr = array())
	{
		$this->callback =& $callback;
		$this->param_arr =& $param_arr;
	}

	function __destruct()
	{
		call_user_func_array($this->callback, $this->param_arr);
	}
}


// Class ob: wrapper for ob_start used by the preprocessor

class ob
{
	static $in_handler = 0;

	static function start($callback = null, $chunk_size = null, $erase = true)
	{
		null !== $callback && $callback = array(new ob($callback), 'callback');
		return ob_start($callback, $chunk_size, $erase);
	}

	protected function __construct($callback)
	{
		$this->callback = $callback;
	}

	function &callback(&$buffer, $mode)
	{
		$a = self::$in_handler++;
		$buffer = call_user_func_array($this->callback, array(&$buffer, $mode));
		self::$in_handler = $a;
		return $buffer;
	}
}



/**** Pre-configuration stage 1 ****/



$patchwork_private = false;


// Check HTTP validator

/*#>*/unset($_SERVER['HTTP_IF_NONE_MATCH'], $_SERVER['HTTP_IF_MODIFIED_SINCE']);

$a = isset($_SERVER['HTTP_IF_NONE_MATCH'])
	? $_SERVER['HTTP_IF_NONE_MATCH']
	: isset($_SERVER['HTTP_IF_MODIFIED_SINCE']);

if ($a)
{
	if (true === $a)
	{
		// Patch an IE<=6 bug when using ETag + compression
		$a = explode(';', $_SERVER['HTTP_IF_MODIFIED_SINCE'], 2);
		$_SERVER['HTTP_IF_MODIFIED_SINCE'] = $a = strtotime($a[0]);
		$_SERVER['HTTP_IF_NONE_MATCH'] = '"' . dechex($a) . '"';
		$patchwork_private = true;
	}
	else if (27 == strlen($a) && 25 === strspn($a, '0123456789abcdef') && '""' === $a[0] . $a[26])
	{
		$b = PATCHWORK_ZCACHE . $a[1] .'/'. $a[2] .'/'. substr($a, 3, 6) .'.v.txt';
		if (file_exists($b) && substr(file_get_contents($b), 0, 8) === substr($a, 9, 8))
		{
			$private = substr($a, 17, 1);
			$maxage  = hexdec(substr($a, 18, 8));

			header('HTTP/1.1 304 Not Modified');
			header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', $_SERVER['REQUEST_TIME'] + ($private || !$maxage ? 0 : $maxage)));
			header('Cache-Control: max-age=' . $maxage . ($private ? ',private,must' : ',public,proxy') . '-revalidate');
			exit;
		}
	}
}


// Timezone settings

/*#>*/if (!ini_get('date.timezone'))
	ini_get('date.timezone') || ini_set('date.timezone', 'Universal');


/*#>*/$a = file_get_contents(__patchwork_bootstrapper::$pwd . 'data/utf8/quickChecks.txt');
/*#>*/$a = explode("\n", $a);
define('UTF8_NFC_RX', /*<*/'/' . $a[1] . '/u'/*>*/);
define('UTF8_BOM', /*<*/__patchwork_bootstrapper::UTF8_BOM/*>*/);


// Disables mod_deflate who overwrites any custom Vary: header and appends a body to 304 responses.
// Replaced with our own output compression.

/*#>*/if (function_exists('apache_setenv'))
		apache_setenv('no-gzip','1');


/*#>*/if (ini_get('zlib.output_compression'))
		ini_set('zlib.output_compression', false);


// mbstring configuration

/*#>*/if (extension_loaded('mbstring'))
/*#>*/{
/*#>*/	if ('none'  !== mb_substitute_character())
			mb_substitute_character('none');

/*#>*/	if ('UTF-8' !== mb_internal_encoding())
			mb_internal_encoding('UTF-8');

/*#>*/	if ('pass'  !== mb_http_output())
			mb_http_output('pass');

/*#>*/	if ('uni'   !== mb_language() && 'neutral' !== mb_language())
			mb_language('uni');
/*#>*/}


// iconv configuration

/*#>*/ // See http://php.net/manual/en/function.iconv.php#47428
/*#>*/if (!function_exists('iconv') && function_exists('libiconv'))
/*#>*/{
		function iconv($in_charset, $out_charset, $str)
		{
			return libiconv($in_charset, $out_charset, $str);
		}
/*#>*/}

/*#>*/if (extension_loaded('iconv'))
/*#>*/{
/*#>*/	if ('UTF-8//IGNORE' !== iconv_get_encoding('input_encoding'))
			iconv_set_encoding('input_encoding'   , 'UTF-8//IGNORE');

/*#>*/	if ('UTF-8//IGNORE' !== iconv_get_encoding('internal_encoding'))
			iconv_set_encoding('internal_encoding', 'UTF-8//IGNORE');

/*#>*/	if ('UTF-8//IGNORE' !== iconv_get_encoding('output_encoding'))
			iconv_set_encoding('output_encoding'  , 'UTF-8//IGNORE');
/*#>*/}


// EXIF configuration

/*#>*/if (extension_loaded('exif'))
/*#>*/{
/*#>*/	if ('UTF-8' !== ini_get('exif.encode_unicode') && '' !== ini_get('exif.encode_unicode'))
			ini_set('exif.encode_unicode', 'UTF-8');

/*#>*/	if ('UTF-8' !== ini_get('exif.encode_jis') && '' !== ini_get('exif.encode_jis'))
			ini_set('exif.encode_jis', 'UTF-8');
/*#>*/}


// utf8_encode/decode support

/*#>*/if (!function_exists('utf8_encode'))
/*#>*/{
/*#>*/	if (extension_loaded('iconv') && '§' === @iconv('ISO-8859-1', 'UTF-8', "\xA7"))
/*#>*/	{
			function utf8_encode($s) {return iconv('ISO-8859-1', 'UTF-8', $s);}
/*#>*/	}
/*#>*/	else
/*#>*/	{
			function utf8_encode($s)
			{
				ob_start();
				$len = strlen($s);

				for ($i = 0; $i < $len; ++$i)
				{
					if ($s[$i] < "\x80") echo $s[$i];
					else if ($s[$i] < "\xc0") echo "\xc2", $s[$i];
					else echo "\xc3", chr(ord($s[$i]) - 64);
				}

				return ob_get_clean();
			}
/*#>*/	}
/*#>*/}

/*#>*/if (!function_exists('utf8_decode'))
/*#>*/{
		function utf8_decode($s)
		{
			$len = strlen($s);

			for ($i = 0, $j = 0; $i < $len; ++$i, ++$j)
			{
				switch ($s[$i] & "\xf0")
				{
				case "\xc0":
				case "\xd0":
					$c = (ord($s[$i] & "\x1f") << 6) | ord($s[++$i] & "\x3f");
					$s[$j] = $c < 256 ? chr($c) : '?';
					break;

				case "\xf0": ++$i;
				case "\xe0":
					$s[$j] = '?';
					$i += 2;
					break;

				default:
					$s[$j] = $s[$i];
			}

			return substr($s, 0, $j);
		}
/*#>*/}


// Configure PCRE

/*#>*/if (ini_get('pcre.backtrack_limit') < 5000000)
		ini_set('pcre.backtrack_limit',   5000000);

/*#>*/if (ini_get('pcre.recursion_limit') < 10000)
		ini_set('pcre.recursion_limit',   10000);


// Convert ISO-8859-1 URLs to UTF-8 ones

function url_enc_utf8_dec_callback($m) {return urlencode(utf8_encode(urldecode($m[0])));}

if (!preg_match('//u', urldecode($a = $_SERVER['REQUEST_URI'])))
{
	$a = $a !== utf8_decode($a) ? '/' : preg_replace_callback('/(?:%[89a-f][0-9a-f])+/i', 'url_enc_utf8_dec_callback', $a);
	$b = $_SERVER['REQUEST_METHOD'];

	if ('GET' === $b || 'HEAD' === $b)
	{
		header('HTTP/1.1 301 Moved Permanently');
		header('Location: ' . $a);
		exit;
	}
	else
	{
		$_SERVER['REQUEST_URI'] = $a;
		$b = strpos($a, '?');
		$_SERVER['QUERY_STRING'] = false !== $b++ && $b < strlen($a) ? substr($a, $b) : '';
		parse_str($_SERVER['QUERY_STRING'], $_GET);
	}
}


// Input normalization

/*#>*/if (get_magic_quotes_runtime())
		set_magic_quotes_runtime(false);

/*#>*/$h = extension_loaded('mbstring') && ini_get('mbstring.encoding_translation') && 'UTF-8' == ini_get('mbstring.http_input');
/*#>*/if (get_magic_quotes_gpc() || !$h)
/*#>*/{
		$a = array(&$_GET, &$_POST, &$_COOKIE);
		foreach ($_FILES as &$v) $a[] = array(&$v['name'], &$v['type']);

		$len = count($a);
		for ($i = 0; $i < $len; ++$i)
		{
			foreach ($a[$i] as &$v)
			{
				if (is_array($v)) $a[$len++] =& $v;
				else
				{
/*#>*/				if (get_magic_quotes_gpc())
/*#>*/				{
/*#>*/					if (ini_get('magic_quotes_sybase'))
							$v = str_replace("''", "'", $v);
/*#>*/					else
							$v = stripslashes($v);
/*#>*/				}

/*#>*/				if (!$h)
/*#>*/				{
/*#>*/					if (extension_loaded('iconv') && '§' === @iconv('UTF-8', 'UTF-8//IGNORE', "§\xE0"))
/*#>*/					{
							$v = iconv('UTF-8', 'UTF-8//IGNORE', $v);
/*#>*/					}
/*#>*/					else
/*#>*/					{
							# From http://www.w3.org/International/questions/qa-forms-utf-8
							preg_match_all(/*<*/"/(?:[\\x00-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xec\xee\xef][\x80-\xbf]{2}|\xed[\x80-\x9f][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf]{2}|[\xf1-\xf3][\x80-\xbf]{3}|\xf4[\x80-\x8f][\x80-\xbf]{2})+/"/*>*/, $v, $b);
							$v = implode('', $b[0]);
/*#>*/					}
/*#>*/				}
				}
			}

			reset($a[$i]);
			unset($a[$i]);
		}

		unset($a, $v);
/*#>*/}

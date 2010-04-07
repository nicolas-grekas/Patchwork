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
setlocale(LC_ALL, 'C');

define('UTF8_BOM', "\xEF\xBB\xBF");
define('IS_WINDOWS', /*<*/'\\' === DIRECTORY_SEPARATOR/*>*/);
define('IS_POSTING', 'POST' === $_SERVER['REQUEST_METHOD']);

$_REQUEST = array(); // $_REQUEST is an open door to security problems.


// basic aliasing

/**/patchwork_bootstrapper::aliasFunction('rand',       'mt_rand',       array('min', 'max'));
/**/patchwork_bootstrapper::aliasFunction('getrandmax', 'mt_getrandmax', array());


// mbstring configuration

/**/if (!function_exists('mb_stripos'))
/**/{
/**/	patchwork_bootstrapper::aliasFunction('mb_stripos',  'utf8_mbstring_520::stripos',    array('haystack', 'needle', 'offset' => 0,   'encoding' => INF));
/**/	patchwork_bootstrapper::aliasFunction('mb_stristr',  'utf8_mbstring_520::stristr',    array('haystack', 'needle', 'part' => false, 'encoding' => INF));
/**/	patchwork_bootstrapper::aliasFunction('mb_strrchr',  'utf8_mbstring_520::strrchr',    array('haystack', 'needle', 'part' => false, 'encoding' => INF));
/**/	patchwork_bootstrapper::aliasFunction('mb_strrichr', 'utf8_mbstring_520::strrichr',   array('haystack', 'needle', 'part' => false, 'encoding' => INF));
/**/	patchwork_bootstrapper::aliasFunction('mb_strripos', 'utf8_mbstring_520::strripos',   array('haystack', 'needle', 'offset' => 0,   'encoding' => INF));
/**/	patchwork_bootstrapper::aliasFunction('mb_strrpos',  'utf8_mbstring_520::mb_strrpos', array('haystack', 'needle', 'offset' => 0,   'encoding' => INF));
/**/	patchwork_bootstrapper::aliasFunction('mb_strstr',   'utf8_mbstring_520::strstr',     array('haystack', 'needle', 'part' => false, 'encoding' => INF));
/**/
/**/	patchwork_bootstrapper::aliasFunction('mb_strrpos_500', extension_loaded('mbstring') ? 'mb_strrpos' : 'utf8_mbstring_500::strrpos', array('haystack', 'needle', 'encoding' => INF));
/**/}
/**/else if (3 & (int) @ini_get('mbstring.func_overload'))
/**/{
/**/	if (1  & (int) @ini_get('mbstring.func_overload'))
/**/	{
/**/		patchwork_bootstrapper::aliasFunction('mail', 'utf8_mbstring_noOverload::mail', array('to', 'subject', 'message', 'additional_headers' => '', 'additional_parameters' => ''));
/**/	}
/**/
/**/	if (2 & (int) @ini_get('mbstring.func_overload'))
/**/	{
/**/		patchwork_bootstrapper::aliasFunction('strlen',  'utf8_mbstring_noOverload::strlen',  array('s'));
/**/		patchwork_bootstrapper::aliasFunction('strpos',  'utf8_mbstring_noOverload::strpos',  array('haystack', 'needle', 'offset' => 0));
/**/		patchwork_bootstrapper::aliasFunction('strrpos', 'utf8_mbstring_noOverload::strrpos', array('haystack', 'needle', 'offset' => 0));
/**/		patchwork_bootstrapper::aliasFunction('substr',  'utf8_mbstring_noOverload::substr',  array('string', 'start', 'length' => INF));
/**/	}
/**/}

/**/if (extension_loaded('mbstring'))
/**/{
		mb_internal_encoding('UTF-8');
		mb_regex_encoding('UTF-8');

/**/	if ('none'  !== strtolower(mb_substitute_character()))
			mb_substitute_character('none');

/**/	if ('pass'  !== strtolower(mb_http_output()))
			mb_http_output('pass');

/**/	if ('uni'   !== strtolower(mb_language()) && 'neutral' !== strtolower(mb_language()))
			mb_language('uni');
/**/}
/**/else
/**/{
		define('MB_OVERLOAD_MAIL',   1);
		define('MB_OVERLOAD_STRING', 2);
		define('MB_OVERLOAD_REGEX',  4);
		define('MB_CASE_UPPER', 0);
		define('MB_CASE_LOWER', 1);
		define('MB_CASE_TITLE', 2);

/**/	patchwork_bootstrapper::aliasFunction('mb_convert_encoding',     'utf8_mbstring_500::convert_encoding',     array('str', 'to_encoding', 'from_encoding' => INF));
/**/	patchwork_bootstrapper::aliasFunction('mb_decode_mimeheader',    'utf8_iconv::mime_decode',                 array('str'));
/**/	patchwork_bootstrapper::aliasFunction('mb_convert_case',         'utf8_mbstring_500::convert_case',         array('str', 'charset' => INF, 'transfer_encoding' => INF, 'linefeed' => INF, 'indent' => INF));
/**/	patchwork_bootstrapper::aliasFunction('mb_internal_encoding',    'utf8_mbstring_500::internal_encoding',    array('str', 'mode', 'encoding' => INF));
/**/	patchwork_bootstrapper::aliasFunction('mb_list_encodings',       'utf8_mbstring_500::list_encodings',       array('encoding' => INF));
/**/	patchwork_bootstrapper::aliasFunction('mb_parse_str',            'parse_str',                               array());
/**/	patchwork_bootstrapper::aliasFunction('mb_strlen',               'utf8_mbstring_500::strlen',               array('str', 'encoding' => INF));
/**/	patchwork_bootstrapper::aliasFunction('mb_strpos',               'utf8_mbstring_500::strpos',               array('haystack', 'needle', 'offset' => 0, 'encoding' => INF));
/**/	patchwork_bootstrapper::aliasFunction('mb_strtolower',           'utf8_mbstring_500::strtolower',           array('str', 'encoding' => INF));
/**/	patchwork_bootstrapper::aliasFunction('mb_strtoupper',           'utf8_mbstring_500::strtoupper',           array('str', 'encoding' => INF));
/**/	patchwork_bootstrapper::aliasFunction('mb_substitute_character', 'utf8_mbstring_500::substitute_character', array('char' => INF));
/**/	patchwork_bootstrapper::aliasFunction('mb_substr_count',         'substr_count',                            array('haystack',  'needle'));
/**/	patchwork_bootstrapper::aliasFunction('mb_substr',               'utf8_mbstring_500::substr',               array('str', 'start', 'length' => PHP_INT_MAX, 'encoding' => INF));
/**/	patchwork_bootstrapper::aliasFunction('mb_strrpos_500',          'utf8_mbstring_500::mb_strrpos',           array('haystack', 'needle', 'part' => false, 'encoding' => INF));
/**/}


// $_SERVER variables manipulations

if (!isset($_SERVER['HTTP_HOST']) || strspn($_SERVER['HTTP_HOST'], 'eiasntroludcmpghv.fb:-q102yx9jk3548w67z') !== strlen($_SERVER['HTTP_HOST']))
{
	die('Invalid HTTP/1.1 Host header');
}


// Fix some $_SERVER variables under Windows

/**/if ('\\' === DIRECTORY_SEPARATOR)
/**/{
		// IIS compatibility

/**/	if (!isset($_SERVER['REQUEST_URI']))
			$_SERVER['REQUEST_URI'] = isset($_SERVER['HTTP_X_REWRITE_URL']) ? $_SERVER['HTTP_X_REWRITE_URL'] : $_SERVER['URL'];

/**/	if (!isset($_SERVER['SERVER_ADDR']))
			$_SERVER['SERVER_ADDR'] = '127.0.0.1';

/**/	if (!isset($_SERVER['QUERY_STRING']))
/**/	{
			$a = $_SERVER['REQUEST_URI'];
			$b = strpos($a, '?');
			$_SERVER['QUERY_STRING'] = false !== $b++ && isset($a[$b]) ? substr($a, $b) : '';
/**/	}
/**/}


if (isset($_SERVER['HTTPS']))
{
	if ('on' === strtolower($_SERVER['HTTPS']) || '1' == $_SERVER['HTTPS']) $_SERVER['HTTPS'] = 'on';
	else unset($_SERVER['HTTPS']);
}


// Utility functions

function ini_get_bool($a)
{
	switch ($b = strtolower(@ini_get($a)))
	{
	case 'on':
	case 'yes':
	case 'true':
		return 'assert.active' !== $a;

	case 'stdout':
	case 'stderr':
		return 'display_errors' === $a;

	default:
		return (bool) (int) $b;
	}
}

/**/$a = '' === basename('§');

define('PATCHWORK_BUGGY_BASENAME', /*<*/$a/*>*/);

/**/if ($a)
/**/{
		function patchwork_basename($path, $suffix = '')
		{
			$path = rtrim($path, /*<*/'/' . ('\\' === DIRECTORY_SEPARATOR ? '\\' : '')/*>*/);

/**/		if ('\\' === DIRECTORY_SEPARATOR)
				$r = strrpos(strtr($path, '\\', '/'), '/');
/**/		else
				$r = strrpos($path, '/');

			false !== $r && $path = substr($path, $r + 1);

			return substr(basename('.' . $path, $suffix), 1);
		}

		function patchwork_pathinfo($path, $option = INF)
		{
			$path = rawurlencode($path);
			$path = str_replace('%2F', '/' , $path);
			$path = str_replace('%5C', '\\', $path);

			$path = INF === $option ? pathinfo($path) : pathinfo($path, $option);

			return is_array($path)
				? array_map('rawurldecode', $path)
				: rawurldecode($path);
		}
/**/}
/**/else
/**/{
		function patchwork_basename($path, $suffix = '')
		{
			return basename($path, $suffix);
		}

		function patchwork_pathinfo($path, $option = INF)
		{
			return INF === $option ? pathinfo($path) : pathinfo($path, $option);
		}
/**/}

/**/$a = function_exists('realpath') ? @realpath('.') : false;
/**/if (!$a || '.' === $a)
/**/{
/**/	if (function_exists('getcwd') && @getcwd()) $a = true;
/**/	else
/**/	{
/**/		$a = function_exists('get_included_files') ? @get_included_files() : '';
/**/		$a = $a ? $a[0] : (!empty($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '.');
/**/		$a = dirname($a);
/**/	}
/**/}
/**/else $a = false;

define('PATCHWORK_BUGGY_REALPATH', /*<*/(bool) $a/*>*/);

function patchwork_getcwd()
{
/**/if (function_exists('getcwd') && @getcwd())
/**/{
		return getcwd();
/**/}
/**/else if (false === $a)
/**/{
		return realpath('.');
/**/}
/**/else
/**/{
		return /*<*/$a/*>*/;
/**/}
}

/**/if (false !== $a)
/**/{
		function patchwork_realpath($a)
		{
			do
			{
				if (isset($a[0]))
				{
/**/				if ('\\' === DIRECTORY_SEPARATOR)
/**/				{
						if ('/' === $a[0] || '\\' === $a[0])
						{
							$a = 'c:' . $a;
							break;
						}

						if (false !== strpos($a, ':')) break;
/**/				}
/**/				else
/**/				{
						if ('/' === $a[0]) break;
/**/				}
				}

/**/			if (true === $a)
					$cwd = getcwd();
/**/			else
					$cwd = /*<*/$a/*>*/;

				$a = $cwd . /*<*/DIRECTORY_SEPARATOR/*>*/ . $a;

				break;
			}
			while (0);

			if (isset($cwd) && '.' === $cwd) $prefix = '.';
			else
			{
/**/			if ('\\' === DIRECTORY_SEPARATOR)
/**/			{
					$prefix = strtoupper($a[0]) . ':\\';
					$a = substr($a, 2);
/**/			}
/**/			else
/**/			{
					$prefix = '/';
/**/			}
			}

/**/		if ('\\' === DIRECTORY_SEPARATOR)
				$a = strtr($a, '/', '\\');

			$a = explode(/*<*/DIRECTORY_SEPARATOR/*>*/, $a);
			$b = array();

			foreach ($a as $a)
			{
				if (!isset($a[0]) || '.' === $a) continue;
				if ('..' === $a) $b && array_pop($b);
				else $b[]= $a;
			}

			$a = $prefix . implode(/*<*/DIRECTORY_SEPARATOR/*>*/, $b);

/**/		if ('\\' === DIRECTORY_SEPARATOR)
				$a = strtolower($a);

			return file_exists($a) ? $a : false;
		}
/**/}
/**/else
/**/{
		function patchwork_realpath($a) {return realpath($a);}
/**/}


// Class ob: wrapper for ob_start

/**/patchwork_bootstrapper::aliasFunction('ob_start', 'ob::start', array('callback' => null, 'chunk_size' => null, 'erase' => true));

class ob
{
	static

	$in_handler = 0,
	$clear = false;


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
		self::$clear && $buffer = '';
		$buffer = call_user_func_array($this->callback, array(&$buffer, $mode));
		self::$in_handler = $a;
		self::$clear = false;
		return $buffer;
	}
}


// Timezone settings

/**/if (!@ini_get('date.timezone'))
	@(ini_get('date.timezone') || ini_set('date.timezone', 'Universal'));


// Turn off magic_quotes_runtime

/**/if (function_exists('get_magic_quotes_runtime') && @get_magic_quotes_runtime())
/**/{
/**/	@set_magic_quotes_runtime(false);
/**/	@get_magic_quotes_runtime()
/**/		&& die('Patchwork Error: failed to turn off magic_quotes_runtime');

		@set_magic_quotes_runtime(false);
/**/}


// iconv configuration

/**/ // See http://php.net/manual/en/function.iconv.php#47428
/**/if (!function_exists('iconv') && function_exists('libiconv'))
/**/{
/**/	patchwork_bootstrapper::aliasFunction('iconv', 'libiconv', array('in_charset', 'out_charset', 'str'));
/**/}

/**/if (extension_loaded('iconv'))
/**/{
/**/	if ('UTF-8//IGNORE' !== iconv_get_encoding('input_encoding'))
			iconv_set_encoding('input_encoding'   , 'UTF-8//IGNORE');

/**/	if ('UTF-8//IGNORE' !== iconv_get_encoding('internal_encoding'))
			iconv_set_encoding('internal_encoding', 'UTF-8//IGNORE');

/**/	if ('UTF-8//IGNORE' !== iconv_get_encoding('output_encoding'))
			iconv_set_encoding('output_encoding'  , 'UTF-8//IGNORE');
/**/}
/**/else
/**/{
		define('ICONV_IMPL', 'patchwork');
		define('ICONV_VERSION', '1.0');
		define('ICONV_MIME_DECODE_STRICT', 1);
		define('ICONV_MIME_DECODE_CONTINUE_ON_ERROR', 2);

/**/	patchwork_bootstrapper::aliasFunction('iconv', 'utf8_iconv::iconv', array('in_charset', 'out_charset', 'str'));
/**/	patchwork_bootstrapper::aliasFunction('iconv_get_encoding', 'utf8_iconv::get_encoding', array('type' => 'all'));
/**/	patchwork_bootstrapper::aliasFunction('iconv_set_encoding', 'utf8_iconv::set_encoding', array('type', 'charset'));
/**/	patchwork_bootstrapper::aliasFunction('iconv_mime_encode',  'utf8_iconv::mime_encode',  array('field_name', 'field_value', 'pref' => INF));
/**/	patchwork_bootstrapper::aliasFunction('ob_iconv_handler',   'utf8_iconv::ob_handler',   array('buffer', 'mode'));
/**/	patchwork_bootstrapper::aliasFunction('iconv_mime_decode_headers', 'utf8_iconv::mime_decode_headers', array('encoded_headers', 'mode' => 2, 'charset' => INF));
/**/
/**/	if (extension_loaded('mbstring'))
/**/	{
/**/		patchwork_bootstrapper::aliasFunction('iconv_strlen',  'mb_strlen',  array('s', 'encoding' => INF));
/**/		patchwork_bootstrapper::aliasFunction('iconv_strpos',  'mb_strpos',  array('haystack', 'needle', 'offset' => 0, 'encoding' => INF));
/**/		patchwork_bootstrapper::aliasFunction('iconv_strrpos', 'mb_strrpos', array('haystack', 'needle',                'encoding' => INF));
/**/		patchwork_bootstrapper::aliasFunction('iconv_substr',  'mb_substr',  array('s', 'start', 'length' => PHP_INT_MAX, 'encoding' => INF));
/**/		patchwork_bootstrapper::aliasFunction('iconv_mime_decode', 'mb_decode_mimeheader', array('encoded_headers', 'mode' => 2, 'charset' => INF));
/**/	}
/**/	else
/**/	{
/**/		extension_loaded('xml')
/**/			? patchwork_bootstrapper::aliasFunction('iconv_strlen', 'utf8_iconv::strlen1', array('s', 'encoding' => INF))
/**/			: patchwork_bootstrapper::aliasFunction('iconv_strlen', 'utf8_iconv::strlen2', array('s', 'encoding' => INF));
/**/
/**/		patchwork_bootstrapper::aliasFunction('iconv_strpos',  'utf8_mbstring_500::strpos',  array('haystack', 'needle', 'offset' => 0, 'encoding' => INF));
/**/		patchwork_bootstrapper::aliasFunction('iconv_strrpos', 'utf8_mbstring_500::strrpos', array('haystack', 'needle',                'encoding' => INF));
/**/		patchwork_bootstrapper::aliasFunction('iconv_substr',  'utf8_mbstring_500::substr',  array('s', 'start', 'length' => PHP_INT_MAX, 'encoding' => INF));
/**/		patchwork_bootstrapper::aliasFunction('iconv_mime_decode',  'utf8_iconv::mime_decode', array('encoded_headers', 'mode' => 2, 'charset' => INF));
/**/	}
/**/}


// EXIF configuration

/**/if (extension_loaded('exif'))
/**/{
/**/	if (@('UTF-8' !== strtoupper(ini_get('exif.encode_unicode')) && ini_get('exif.encode_unicode')))
			@ini_set('exif.encode_unicode', 'UTF-8');

/**/	if (@('UTF-8' !== strtoupper(ini_get('exif.encode_jis')) && ini_get('exif.encode_jis')))
			@ini_set('exif.encode_jis', 'UTF-8');
/**/}


// utf8_encode/decode support

/**/if (!function_exists('utf8_encode'))
/**/{
/**/	if (extension_loaded('iconv') && '§' === @iconv('ISO-8859-1', 'UTF-8', "\xA7"))
/**/	{
			function utf8_encode($s) {return iconv('ISO-8859-1', 'UTF-8', $s);}
/**/	}
/**/	else
/**/	{
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
/**/	}
/**/}

/**/if (!function_exists('utf8_decode'))
/**/{
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
			}

			return substr($s, 0, $j);
		}
/**/}

// utf8_encode/decode support enhanced to Windows-1252

function patchwork_utf8_encode($s)
{
	static $map = array(
		"\xc2\x80" => '€', "\xc2\x82" => '‚', "\xc2\x83" => 'ƒ',
		"\xc2\x84" => '„', "\xc2\x85" => '…', "\xc2\x86" => '†',
		"\xc2\x87" => '‡', "\xc2\x88" => 'ˆ', "\xc2\x89" => '‰',
		"\xc2\x8a" => 'Š', "\xc2\x8b" => '‹', "\xc2\x8c" => 'Œ',
		"\xc2\x8e" => 'Ž', "\xc2\x91" => '‘', "\xc2\x92" => '’',
		"\xc2\x93" => '“', "\xc2\x94" => '”', "\xc2\x95" => '•',
		"\xc2\x96" => '–', "\xc2\x97" => '—', "\xc2\x98" => '˜',
		"\xc2\x99" => '™', "\xc2\x9a" => 'š', "\xc2\x9b" => '›',
		"\xc2\x9c" => 'œ', "\xc2\x9e" => 'ž', "\xc2\x9f" => 'Ÿ',
	);

	return strtr(utf8_encode($s), $map);
}

function patchwork_utf8_decode($s)
{
	static $map = array(
		'€' => "\xc2\x80", '‚' => "\xc2\x82", 'ƒ' => "\xc2\x83",
		'„' => "\xc2\x84", '…' => "\xc2\x85", '†' => "\xc2\x86",
		'‡' => "\xc2\x87", 'ˆ' => "\xc2\x88", '‰' => "\xc2\x89",
		'Š' => "\xc2\x8a", '‹' => "\xc2\x8b", 'Œ' => "\xc2\x8c",
		'Ž' => "\xc2\x8e", '‘' => "\xc2\x91", '’' => "\xc2\x92",
		'“' => "\xc2\x93", '”' => "\xc2\x94", '•' => "\xc2\x95",
		'–' => "\xc2\x96", '—' => "\xc2\x97", '˜' => "\xc2\x98",
		'™' => "\xc2\x99", 'š' => "\xc2\x9a", '›' => "\xc2\x9b",
		'œ' => "\xc2\x9c", 'ž' => "\xc2\x9e", 'Ÿ' => "\xc2\x9f",
	);

	return utf8_decode(strtr($s, $map));
}


// Configure PCRE

/**/preg_match('/^.$/u', '§') || die('Patchwork Error: PCRE is not compiled with UTF-8 support');

/**/if (@ini_get('pcre.backtrack_limit') < 5000000)
		@ini_set('pcre.backtrack_limit', 5000000);

/**/if (@ini_get('pcre.recursion_limit') < 10000)
		@ini_set('pcre.recursion_limit', 10000);


function patchwork_http_socket($host, $port, $ssl, $timeout = 30)
{
	if ($port <= 0) $port = $ssl ? '443' : '80';
	$ssl = $ssl ? 'ssl' : 'tcp';

	if (false !== strpos($host, ':'))
	{
		// Workaround for http://bugs.php.net/48805

		if ('[]' !== substr($host, 0, 1) . substr($host, -1)) $host = '[' . $host . ']';
		$h = stream_context_create(array('socket' => array('bindto' => '[::]:0')));
		$h = stream_socket_client("{$ssl}://{$host}:{$port}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $h);
	}
	else
	{
		strspn(substr($host, -1), '0123456789') || $host .= '.';
		$h = fsockopen("{$ssl}://{$host}", $port, $errno, $errstr, $timeout);
	}

	if (!$h) throw new Exception("Socket error n°{$errno}: {$errstr}");

	return $h;
}


// intl configuration

/**/if (!extension_loaded('intl'))
/**/{
/**/	patchwork_bootstrapper::aliasFunction('normalizer_is_normalized', 'Normalizer::isNormalized', array('s', 'form' => 'NFC'));
/**/	patchwork_bootstrapper::aliasFunction('normalizer_normalize',     'Normalizer::normalize',    array('s', 'form' => 'NFC'));
/**/
/**/	patchwork_bootstrapper::aliasFunction('grapheme_stripos',  'utf8_intl::grapheme_stripos',  array('s', 'needle', 'offset' => 0));
/**/	patchwork_bootstrapper::aliasFunction('grapheme_stristr',  'utf8_intl::grapheme_stristr',  array('s', 'needle', 'before_needle' => false));
/**/	patchwork_bootstrapper::aliasFunction('grapheme_strlen',   'utf8_intl::grapheme_strlen',   array('s'));
/**/	patchwork_bootstrapper::aliasFunction('grapheme_strpos',   'utf8_intl::grapheme_strpos',   array('s', 'needle', 'offset' => 0));
/**/	patchwork_bootstrapper::aliasFunction('grapheme_strripos', 'utf8_intl::grapheme_strripos', array('s', 'needle', 'offset' => 0));
/**/	patchwork_bootstrapper::aliasFunction('grapheme_strrpos',  'utf8_intl::grapheme_strrpos',  array('s', 'needle', 'offset' => 0));
/**/	patchwork_bootstrapper::aliasFunction('grapheme_strstr',   'utf8_intl::grapheme_strstr',   array('s', 'needle', 'before_needle' => false));
/**/	patchwork_bootstrapper::aliasFunction('grapheme_substr',   'utf8_intl::grapheme_substr',   array('s', 'start', 'len' => INF));
/**/}

<?php /***** vi: set encoding=utf-8 expandtab shiftwidth=4: ****************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


define('IS_POSTING', 'POST' === $_SERVER['REQUEST_METHOD']);

$_REQUEST = array(); // $_REQUEST is an open door to security problems.


// Basic aliasing

/**/ /*<*/patchwork_bootstrapper::alias('rand',       'mt_rand',       array('$min' => 0, '$max' => mt_getrandmax()))/*>*/;
/**/ /*<*/patchwork_bootstrapper::alias('getrandmax', 'mt_getrandmax', array())/*>*/;


// Changing default charset to UTF-8, adding new $double_encode parameter (since 5.2.3)

/**/ /*<*/patchwork_bootstrapper::alias('html_entity_decode', 'html_entity_decode', array('$s', '$style' => ENT_COMPAT, '$charset' => 'UTF-8'))/*>*/;

/**/if (PHP_VERSION_ID < 50203)
/**/{
/**/    /*<*/patchwork_bootstrapper::alias('htmlspecialchars', 'patchwork_PHP_strings::htmlspecialchars', array('$s', '$style' => ENT_COMPAT, '$charset' => 'UTF-8', '$double_enc' => true))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('htmlentities',     'patchwork_PHP_strings::htmlentities',     array('$s', '$style' => ENT_COMPAT, '$charset' => 'UTF-8', '$double_enc' => true))/*>*/;
/**/}
/**/else
/**/{
/**/    // No alias for htmlspecialchars() because ISO-8859-1 and UTF-8 are both compatible with ASCII, where the HTML_SPECIALCHARS table lies
/**/    /*<*/patchwork_bootstrapper::alias('htmlentities', 'htmlentities', array('$s', '$style' => ENT_COMPAT, '$charset' => 'UTF-8', '$double_enc' => true))/*>*/;
/**/}


// Fix 5.2.9 array_unique() default sort flag
/**/if (PHP_VERSION_ID == 50209)
/**/    /*<*/patchwork_bootstrapper::alias('array_unique', 'array_unique', array('$array', '$sort_flags' => SORT_STRING))/*>*/;

// Workaround http://bugs.php.net/37394
/**/if (PHP_VERSION_ID < 50200)
/**/    /*<*/patchwork_bootstrapper::alias('substr_compare', 'patchwork_PHP_strings::substr_compare', array('$main_str', '$str', '$offset', '$length' => INF, '$case_insensitivity' => false))/*>*/;


// mbstring configuration

/**/if (!function_exists('mb_stripos'))
/**/{
/**/    /*<*/patchwork_bootstrapper::alias('mb_stripos',  'patchwork_PHP_mbstring::stripos',    array('$s', '$needle', '$offset' => 0,   '$enc' => INF))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('mb_stristr',  'patchwork_PHP_mbstring::stristr',    array('$s', '$needle', '$part' => false, '$enc' => INF))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('mb_strrchr',  'patchwork_PHP_mbstring::strrchr',    array('$s', '$needle', '$part' => false, '$enc' => INF))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('mb_strrichr', 'patchwork_PHP_mbstring::strrichr',   array('$s', '$needle', '$part' => false, '$enc' => INF))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('mb_strripos', 'patchwork_PHP_mbstring::strripos',   array('$s', '$needle', '$offset' => 0,   '$enc' => INF))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('mb_strrpos',  'patchwork_PHP_mbstring::strrpos',    array('$s', '$needle', '$offset' => 0,   '$enc' => INF))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('mb_strstr',   'patchwork_PHP_mbstring::strstr',     array('$s', '$needle', '$part' => false, '$enc' => INF))/*>*/;
/**/
/**/    /*<*/patchwork_bootstrapper::alias('mb_strrpos_500', extension_loaded('mbstring') ? 'mb_strrpos' : 'patchwork_PHP_mbstring_500::strrpos', array('$s', '$needle', '$enc' => INF))/*>*/;
/**/}
/**/else if (3 & (int) @ini_get('mbstring.func_overload'))
/**/{
/**/    if (1  & (int) @ini_get('mbstring.func_overload'))
/**/    {
/**/        /*<*/patchwork_bootstrapper::alias('mail', 'patchwork_PHP_mbstring_no::mail', array('$to', '$subject', '$message', '$headers' => '', '$params' => ''))/*>*/;
/**/    }
/**/
/**/    if (2 & (int) @ini_get('mbstring.func_overload'))
/**/    {
/**/        /*<*/patchwork_bootstrapper::alias('strlen',  'patchwork_PHP_mbstring_no::strlen',  array('$s'))/*>*/;
/**/        /*<*/patchwork_bootstrapper::alias('strpos',  'patchwork_PHP_mbstring_no::strpos',  array('$s', '$needle', '$offset' => 0))/*>*/;
/**/        /*<*/patchwork_bootstrapper::alias('strrpos', 'patchwork_PHP_mbstring_no::strrpos', array('$s', '$needle', '$offset' => 0))/*>*/;
/**/        /*<*/patchwork_bootstrapper::alias('substr',  'patchwork_PHP_mbstring_no::substr',  array('$s', '$start', '$length' => INF))/*>*/;
/**/    }
/**/}

/**/if (extension_loaded('mbstring'))
/**/{
/**/    ini_get_bool('mbstring.encoding_translation')
/**/        && !in_array(strtolower(ini_get('mbstring.http_input')), array('pass', '8bit', 'utf-8'))
/**/        && die('Patchwork error: Please disable "mbstring.encoding_translation" or set "mbstring.http_input" to "pass" or "utf-8"');

        mb_regex_encoding('UTF-8');
        @ini_set('mbstring.script_encoding', 'pass');

/**/    if ('utf-8' !== strtolower(mb_internal_encoding()))
            mb_internal_encoding('UTF-8')   + @ini_set('mbstring.internal_encoding', 'UTF-8');

/**/    if ('none'  !== strtolower(mb_substitute_character()))
            mb_substitute_character('none') + @ini_set('mbstring.substitute_character', 'none');

/**/    if (!in_array(strtolower(mb_http_output()), array('pass', '8bit')))
            mb_http_output('pass')          + @ini_set('mbstring.http_output', 'pass');

/**/    if (!in_array(strtolower(mb_language()), array('uni', 'neutral')))
            mb_language('uni')              + @ini_set('mbstring.language', 'uni');
/**/}
/**/else
/**/{
        define('MB_OVERLOAD_MAIL',   1);
        define('MB_OVERLOAD_STRING', 2);
        define('MB_OVERLOAD_REGEX',  4);
        define('MB_CASE_UPPER', 0);
        define('MB_CASE_LOWER', 1);
        define('MB_CASE_TITLE', 2);

/**/    /*<*/patchwork_bootstrapper::alias('mb_convert_encoding',     'patchwork_PHP_mbstring_500::convert_encoding',     array('$s', '$to', '$from' => INF))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('mb_decode_mimeheader',    'patchwork_PHP_mbstring_500::decode_mimeheader',    array('$s'))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('mb_encode_mimeheader',    'patchwork_PHP_mbstring_500::convert_case',         array('$s', '$charset' => INF, '$transfer_enc' => INF, '$lf' => INF, '$indent' => INF))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('mb_convert_case',         'patchwork_PHP_mbstring_500::convert_case',         array('$s', '$mode', '$enc' => INF))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('mb_internal_encoding',    'patchwork_PHP_mbstring_500::internal_encoding',    array('$enc' => INF))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('mb_list_encodings',       'patchwork_PHP_mbstring_500::list_encodings',       array())/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('mb_parse_str',            'parse_str',                                          array('$s', '&$result' => array()))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('mb_strlen',               'patchwork_PHP_mbstring_500::strlen',               array('$s', '$enc' => INF))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('mb_strpos',               'patchwork_PHP_mbstring_500::strpos',               array('$s', '$needle', '$offset' => 0, '$enc' => INF))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('mb_strtolower',           'patchwork_PHP_mbstring_500::strtolower',           array('$s', '$enc' => INF))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('mb_strtoupper',           'patchwork_PHP_mbstring_500::strtoupper',           array('$s', '$enc' => INF))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('mb_substitute_character', 'patchwork_PHP_mbstring_500::substitute_character', array('$char' => INF))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('mb_substr_count',         'substr_count',                                       array('$s',  '$needle'))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('mb_substr',               'patchwork_PHP_mbstring_500::substr',               array('$s', '$start', '$length' => PHP_INT_MAX, '$enc' => INF))/*>*/;
/**/}


// Fix some $_SERVER variables

if (!isset($_SERVER['HTTP_HOST']) || '' !== trim($_SERVER['HTTP_HOST'], 'eiasntroludcmpghv.fb:-q102yx9jk3548w67z'))
{
    die('Invalid HTTP/1.1 Host header');
}

/**/if ('\\' === DIRECTORY_SEPARATOR)
/**/{
        // IIS compatibility

/**/    if (!isset($_SERVER['REQUEST_URI']))
            $_SERVER['REQUEST_URI'] = isset($_SERVER['HTTP_X_REWRITE_URL']) ? $_SERVER['HTTP_X_REWRITE_URL'] : $_SERVER['URL'];

/**/    if (!isset($_SERVER['SERVER_ADDR']))
            $_SERVER['SERVER_ADDR'] = '127.0.0.1';

/**/    if (!isset($_SERVER['QUERY_STRING']))
/**/    {
            $a = $_SERVER['REQUEST_URI'];
            $b = strpos($a, '?');
            $_SERVER['QUERY_STRING'] = false !== $b++ && isset($a[$b]) ? substr($a, $b) : '';
/**/    }
/**/}


$_SERVER['HTTPS'] = isset($_SERVER['HTTPS']) && ('on' === strtolower($_SERVER['HTTPS']) || '1' == $_SERVER['HTTPS']) ? 'on' : null;


// basename() and pathinfo() are locale sensitive, but this is not what we want!

/**/if ('' === basename('§'))
/**/{
/**/    /*<*/patchwork_bootstrapper::alias('basename', 'patchwork_PHP_fs::basename', array('$path', '$suffix' => ''))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('pathinfo', 'patchwork_PHP_fs::pathinfo', array('$path', '$option' => INF))/*>*/;
/**/}


// Class ob: wrapper for ob_start()

/**/ /*<*/patchwork_bootstrapper::alias('ob_start', 'ob::start', array('$callback' => null, '$chunk_size' => null, '$erase' => true))/*>*/;

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
    date_default_timezone_set(@date_default_timezone_get());


// Turn off magic quotes runtime

/**/if (function_exists('get_magic_quotes_runtime') && @get_magic_quotes_runtime())
/**/{
/**/    @set_magic_quotes_runtime(false);
/**/    @get_magic_quotes_runtime()
/**/        && die('Patchwork error: Failed to turn off magic_quotes_runtime');

        @set_magic_quotes_runtime(false);
/**/}


// iconv configuration

/**/ // See http://php.net/manual/en/function.iconv.php#47428
/**/if (!function_exists('iconv') && function_exists('libiconv'))
/**/{
/**/    /*<*/patchwork_bootstrapper::alias('iconv', 'libiconv', array('$from', '$to', '$s'))/*>*/;
/**/}

/**/if (extension_loaded('iconv'))
/**/{
/**/    if ('UTF-8//IGNORE' !== iconv_get_encoding('input_encoding'))
            iconv_set_encoding('input_encoding'   , 'UTF-8//IGNORE') + @ini_set('iconv.input_encoding',    'UTF-8//IGNORE');

/**/    if ('UTF-8//IGNORE' !== iconv_get_encoding('internal_encoding'))
            iconv_set_encoding('internal_encoding', 'UTF-8//IGNORE') + @ini_set('iconv.internal_encoding', 'UTF-8//IGNORE');

/**/    if ('UTF-8//IGNORE' !== iconv_get_encoding('output_encoding'))
            iconv_set_encoding('output_encoding'  , 'UTF-8//IGNORE') + @ini_set('iconv.output_encoding',   'UTF-8//IGNORE');
/**/}
/**/else
/**/{
        define('ICONV_IMPL', 'patchwork');
        define('ICONV_VERSION', '1.0');
        define('ICONV_MIME_DECODE_STRICT', 1);
        define('ICONV_MIME_DECODE_CONTINUE_ON_ERROR', 2);

/**/    /*<*/patchwork_bootstrapper::alias('iconv', 'patchwork_PHP_iconv::iconv', array('$from', '$to', '$s'))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('iconv_get_encoding', 'patchwork_PHP_iconv::get_encoding', array('$type' => 'all'))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('iconv_set_encoding', 'patchwork_PHP_iconv::set_encoding', array('$type', '$charset'))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('iconv_mime_encode',  'patchwork_PHP_iconv::mime_encode',  array('$name', '$value', '$pref' => INF))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('ob_iconv_handler',   'patchwork_PHP_iconv::ob_handler',   array('$buffer', '$mode'))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('iconv_mime_decode_headers', 'patchwork_PHP_iconv::mime_decode_headers', array('$encoded_headers', '$mode' => 2, '$charset' => INF))/*>*/;
/**/
/**/    if (extension_loaded('mbstring'))
/**/    {
/**/        /*<*/patchwork_bootstrapper::alias('iconv_strlen',  'mb_strlen',  array('$s', '$enc' => INF))/*>*/;
/**/        /*<*/patchwork_bootstrapper::alias('iconv_strpos',  'mb_strpos',  array('$s', '$needle', '$offset' => 0, '$enc' => INF))/*>*/;
/**/        /*<*/patchwork_bootstrapper::alias('iconv_strrpos', 'mb_strrpos', array('$s', '$needle',                 '$enc' => INF))/*>*/;
/**/        /*<*/patchwork_bootstrapper::alias('iconv_substr',  'mb_substr',  array('$s', '$start', '$length' => PHP_INT_MAX, '$enc' => INF))/*>*/;
/**/        /*<*/patchwork_bootstrapper::alias('iconv_mime_decode', 'mb_decode_mimeheader', array('$encoded_headers', '$mode' => 2, '$charset' => INF))/*>*/;
/**/    }
/**/    else
/**/    {
/**/        extension_loaded('xml')
/**/            ? /*<*/patchwork_bootstrapper::alias('iconv_strlen', 'patchwork_PHP_iconv::strlen1', array('$s', '$enc' => INF))/*>*/
/**/            : /*<*/patchwork_bootstrapper::alias('iconv_strlen', 'patchwork_PHP_iconv::strlen2', array('$s', '$enc' => INF))/*>*/;
/**/
/**/        /*<*/patchwork_bootstrapper::alias('iconv_strpos',  'patchwork_PHP_mbstring::strpos',  array('$s', '$needle', '$offset' => 0, '$enc' => INF))/*>*/;
/**/        /*<*/patchwork_bootstrapper::alias('iconv_strrpos', 'patchwork_PHP_mbstring::strrpos', array('$s', '$needle',                 '$enc' => INF))/*>*/;
/**/        /*<*/patchwork_bootstrapper::alias('iconv_substr',  'patchwork_PHP_mbstring::substr',  array('$s', '$start', '$length' => PHP_INT_MAX, '$enc' => INF))/*>*/;
/**/        /*<*/patchwork_bootstrapper::alias('iconv_mime_decode',  'patchwork_PHP_iconv::mime_decode', array('$encoded_headers', '$mode' => 2, '$charset' => INF))/*>*/;
/**/    }
/**/}


// EXIF configuration

/**/if (extension_loaded('exif'))
/**/{
/**/    if (@('UTF-8' !== strtoupper(ini_get('exif.encode_unicode')) && ini_get('exif.encode_unicode')))
            @ini_set('exif.encode_unicode', 'UTF-8');

/**/    if (@('UTF-8' !== strtoupper(ini_get('exif.encode_jis')) && ini_get('exif.encode_jis')))
            @ini_set('exif.encode_jis', 'UTF-8');
/**/}


// utf8_encode/decode support enhanced to Windows-1252

/**/ /*<*/patchwork_bootstrapper::alias('utf8_encode', 'patchwork_PHP_strings::utf8_encode', array('$s'))/*>*/;
/**/ /*<*/patchwork_bootstrapper::alias('utf8_decode', 'patchwork_PHP_strings::utf8_decode', array('$s'))/*>*/;


// Configure PCRE

/**/preg_match('/^.$/u', '§') || die('Patchwork error: PCRE is not compiled with UTF-8 support');

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
/**/    /*<*/patchwork_bootstrapper::alias('normalizer_is_normalized', 'Normalizer::isNormalized', array('$s', '$form' => 'NFC'))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('normalizer_normalize',     'Normalizer::normalize',    array('$s', '$form' => 'NFC'))/*>*/;
/**/
/**/    /*<*/patchwork_bootstrapper::alias('grapheme_stripos',  'patchwork_PHP_intl::stripos',  array('$s', '$needle', '$offset' => 0))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('grapheme_stristr',  'patchwork_PHP_intl::stristr',  array('$s', '$needle', '$before_needle' => false))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('grapheme_strlen',   'patchwork_PHP_intl::strlen',   array('$s'))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('grapheme_strpos',   'patchwork_PHP_intl::strpos',   array('$s', '$needle', '$offset' => 0))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('grapheme_strripos', 'patchwork_PHP_intl::strripos', array('$s', '$needle', '$offset' => 0))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('grapheme_strrpos',  'patchwork_PHP_intl::strrpos',  array('$s', '$needle', '$offset' => 0))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('grapheme_strstr',   'patchwork_PHP_intl::strstr',   array('$s', '$needle', '$before_needle' => false))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('grapheme_substr',   'patchwork_PHP_intl::substr',   array('$s', '$start', '$len' => INF))/*>*/;
/**/}


// Workaround for http://bugs.php.net/33140

/**/if ('\\' === DIRECTORY_SEPARATOR && PHP_VERSION_ID < 50200)
/**/{
/**/    /*<*/patchwork_bootstrapper::alias('mkdir', 'patchwork_mkdir', array('$pathname', '$mode' => 0777, '$recursive' => false, '$context' => INF))/*>*/;

        function patchwork_mkdir($pathname, $mode = 0777, $recursive = false, $context = INF)
        {
            return INF === $context
                ? mkdir(strtr($pathname, '/', '\\'), $mode, $recursive)
                : mkdir($pathname, $mode, $recursive, $context);
        }
/**/}

/**/if (!function_exists('spl_object_hash'))
/**/{
/**/    /*<*/patchwork_bootstrapper::alias('spl_object_hash',   'patchwork_PHP_class::spl_object_hash',   array('$object'))/*>*/;
/**/}


// Default serialize precision is 100, but 17 is enough

/**/if (17 != @ini_get('serialize_precision'))
        @ini_set('serialize_precision', 17);


/**/$a = file_get_contents(patchwork_bootstrapper::$pwd . 'data/utf8/quickChecks.txt');
/**/$a = explode("\n", $a);
define('UTF8_NFC_RX',            /*<*/'/' . $a[1] . '/u'/*>*/);
define('PATCHWORK_PROJECT_PATH', /*<*/patchwork_bootstrapper::$cwd   /*>*/);
define('PATCHWORK_ZCACHE',       /*<*/patchwork_bootstrapper::$zcache/*>*/);
define('PATCHWORK_PATH_LEVEL',   /*<*/patchwork_bootstrapper::$last  /*>*/);
define('PATCHWORK_PATH_OFFSET',  /*<*/count(patchwork_bootstrapper::$paths) - patchwork_bootstrapper::$last/*>*/);

$patchwork_path = /*<*/patchwork_bootstrapper::$paths/*>*/;
$_patchwork_abstract = array();
$_patchwork_destruct = array();
$CONFIG = array();


// Utility functions

function patchwork_include($file) {return include $file;}

/**/if (PHP_VERSION_ID < 50300)
/**/{
/**/    /*<*/patchwork_bootstrapper::alias('class_implements',        'patchwork_PHP_class::class_implements',        array('$class', '$autoload' => true))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('class_parents',           'patchwork_PHP_class::class_parents',           array('$class', '$autoload' => true))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('class_exists',            'patchwork_PHP_class::class_exists',            array('$class', '$autoload' => true))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('get_class_methods',       'patchwork_PHP_class::get_class_methods',       array('$class'))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('get_class_vars',          'patchwork_PHP_class::get_class_vars',          array('$class'))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('get_class',               'patchwork_PHP_class::get_class',               array('$obj'))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('get_declared_classes',    'patchwork_PHP_class::get_declared_classes',    array())/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('get_declared_interfaces', 'patchwork_PHP_class::get_declared_interfaces', array())/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('get_parent_class',        'patchwork_PHP_class::get_parent_class',        array('$class'))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('interface_exists',        'patchwork_PHP_class::interface_exists',        array('$class', '$autoload' => true))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('is_a',                    'patchwork_PHP_class::is_a',                    array('$obj', '$class'))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('is_subclass_of',          'patchwork_PHP_class::is_subclass_of',          array('$obj', '$class'))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('method_exists',           'patchwork_PHP_class::method_exists',           array('$class', '$method'))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('property_exists',         'patchwork_PHP_class::property_exists',         array('$class', '$property'))/*>*/;
/**/}

function patchwork_bad_request($message, $url)
{
    if (in_array($_SERVER['REQUEST_METHOD'], array('GET', 'HEAD')))
    {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $url);
    }
    else
    {
        header('HTTP/1.1 400 Bad Request');
        header('Content-Type: text/html; charset=utf-8');

        $message = htmlspecialchars($message);
        $url = htmlspecialchars($url);

        echo <<<EOHTML
<html>
<head><title>400 Bad Request</title></head>
<body>
<h1>400 Bad Request</h1>
<p>{$message}<br> Maybe are you trying to reach <a href="{$url}">this URL</a>?</p>
</body>
</html>
EOHTML;
    }

    exit;
}

function patchwork_shutdown_start()
{
/**/if (function_exists('fastcgi_finish_request'))
        fastcgi_finish_request();

    register_shutdown_function('patchwork_shutdown_end');
}

function patchwork_shutdown_end()
{
    if (empty($GLOBALS['_patchwork_destruct']))
    {
        // See http://bugs.php.net/54157
        register_shutdown_function('session_write_close');
    }
    else
    {
        call_user_func(array(array_shift($GLOBALS['_patchwork_destruct']), '__free'));
        register_shutdown_function(__FUNCTION__);
    }
}

register_shutdown_function('patchwork_shutdown_start');


function patchwork_class2file($class)
{
    if (false !== $a = strrpos($class, '\\'))
    {
        $a += $b = strspn($class, '\\');
        $class =  strtr(substr($class, $b, $a), '\\', '/')
            .'/'. strtr(substr($class, $a+1  ), '_' , '/');
    }
    else
    {
        $class = strtr($class, '_', '/');
    }

    if (false !== strpos($class, '//x'))
    {
/**/    $a = "_/ /!/#/$/%/&/'/(/)/+/,/-/./;/=/@/[/]/^/`/{/}/~";
/**/    $a = array(array(), explode('/', $a));
/**/    foreach ($a[1] as $b) $a[0][] = '//x' . strtoupper(dechex(ord($b)));

        $class = str_replace(/*<*/$a[0]/*>*/, /*<*/$a[1]/*>*/, $class);
    }

    return $class;
}

function patchwork_file2class($file)
{
/**/$a = "_/ /!/#/$/%/&/'/(/)/+/,/-/./;/=/@/[/]/^/`/{/}/~";
/**/$a = array(explode('/', $a), array());
/**/foreach ($a[0] as $b) $a[1][] = '__x' . strtoupper(dechex(ord($b)));

    $file = str_replace(/*<*/$a[0]/*>*/, /*<*/$a[1]/*>*/, $file);
    $file = strtr($file, '/\\', '__');

    return $file;
}


// registerAutoloadPrefix()

$patchwork_autoload_prefix = array();

function registerAutoloadPrefix($class_prefix, $class_to_file_callback)
{
    if ($len = strlen($class_prefix))
    {
        $registry =& $GLOBALS['patchwork_autoload_prefix'];
        $class_prefix = strtolower($class_prefix);
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

function patchworkPath($file, &$last_level = false, $level = false, $base = false)
{
    if (false === $level)
    {
/**/if (IS_WINDOWS)
        if (isset($file[0]) && ('\\' === $file[0] || false !== strpos($file, ':'))) return $file;
        if (isset($file[0]) &&  '/'  === $file[0]) return $file;

        $i = 0;
        $level = /*<*/patchwork_bootstrapper::$last/*>*/;
    }
    else
    {
        0 <= $level && $base = 0;
        $i = /*<*/patchwork_bootstrapper::$last/*>*/ - $level - $base;
        0 > $i && $i = 0;
    }

/**/if (IS_WINDOWS)
        false !== strpos($file, '\\') && $file = strtr($file, '\\', '/');

    if (0 === $i)
    {
        $source = /*<*/patchwork_bootstrapper::$cwd/*>*/ . $file;

/**/    if (IS_WINDOWS)
/**/    {
            if (function_exists('patchwork_file_exists') ? patchwork_file_exists($source) : file_exists($source))
            {
                $last_level = $level;
                return false !== strpos($source, '/') ? strtr($source, '/', '\\') : $source;
            }
/**/    }
/**/    else
/**/    {
            if (file_exists($source))
            {
                $last_level = $level;
                return $source;
            }
/**/    }

    }


    if ($slash = '/' === substr($file, -1)) $file = substr($file, 0, -1);


/**/if ($a = patchwork_bootstrapper::updatedb())
/**/{
        static $db;

        if (!isset($db))
        {
            if (!$db = @dba_popen(/*<*/patchwork_bootstrapper::$cwd . '.patchwork.paths.db'/*>*/, 'rd', /*<*/$a/*>*/))
            {
                require_once /*<*/patchwork_bootstrapper::$pwd . 'class/patchwork/bootstrapper.php'/*>*/;

                $db = patchwork_bootstrapper::fixParentPaths(/*<*/patchwork_bootstrapper::$pwd/*>*/);
            }
        }

        $base = dba_fetch($file, $db);
/**/}
/**/else
/**/{
        $base = md5($file);
        $base = /*<*/patchwork_bootstrapper::$zcache/*>*/ . $base[0] . '/' . $base[1] . '/' . substr($base, 2) . '.path.txt';
        $base = @file_get_contents($base);
/**/}

    if (false !== $base)
    {
        $base = explode(',', $base);
        do if (current($base) >= $i)
        {
            $base = (int) current($base);
            $last_level = $level - $base + $i;

/**/        if (IS_WINDOWS)
                false !== strpos($file, '/') && $file = strtr($file, '/', '\\');

            return $GLOBALS['patchwork_path'][$base] . (0<=$last_level ? $file : substr($file, 6)) . ($slash ? /*<*/DIRECTORY_SEPARATOR/*>*/ : '');
        }
        while (false !== next($base));
    }

    return false;
}


// Check HTTP validator

$patchwork_private = false;

/**/unset($_SERVER['HTTP_IF_NONE_MATCH'], $_SERVER['HTTP_IF_MODIFIED_SINCE']);

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
    else if (27 === strlen($a) && 25 === strspn($a, '0123456789abcdef') && '""' === $a[0] . $a[26])
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


// Disables mod_deflate who overwrites any custom Vary: header and appends a body to 304 responses.
// Replaced with our own output compression.

/**/if (function_exists('apache_setenv'))
        apache_setenv('no-gzip','1');


/**/if (ini_get_bool('zlib.output_compression'))
        @ini_set('zlib.output_compression', false);


// Convert ISO-8859-1 URLs to UTF-8 ones

function url_enc_utf8_dec_callback($m) {return urlencode(patchwork_PHP_strings::utf8_encode(urldecode($m[0])));}

if (!preg_match('//u', urldecode($a = $_SERVER['REQUEST_URI'])))
{
    $a = $a !== patchwork_utf8_decode($a) ? '/' : preg_replace_callback('/(?:%[89A-F][0-9A-F])+/i', 'url_enc_utf8_dec_callback', $a);

    patchwork_bad_request('Requested URL is not a valid urlencoded UTF-8 string.', $a);
}


// Input normalization

/**/$h = @(extension_loaded('mbstring') && ini_get_bool('mbstring.encoding_translation') && 'UTF-8' === strtoupper(ini_get('mbstring.http_input')));
/**/if (!$h || (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc()))
/**/{
        $a = array(&$_GET, &$_POST, &$_COOKIE);
        foreach ($_FILES as &$v) $a[] = array(&$v['name'], &$v['type']);

        $k = count($a);
        for ($i = 0; $i < $k; ++$i)
        {
            foreach ($a[$i] as &$v)
            {
                if (is_array($v)) $a[$k++] =& $v;
                else
                {
/**/                if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc())
/**/                {
/**/                    if (ini_get_bool('magic_quotes_sybase'))
                            $v = str_replace("''", "'", $v);
/**/                    else
                            $v = stripslashes($v);
/**/                }

/**/                if (!$h)
/**/                {
/**/                    if (extension_loaded('iconv') && '§' === @iconv('UTF-8', 'UTF-8//IGNORE', "§\xE0"))
/**/                    {
                            $v = @iconv('UTF-8', 'UTF-8//IGNORE', $v);
/**/                    }
/**/                    else
/**/                    {
                            # From http://www.w3.org/International/questions/qa-forms-utf-8
                            preg_match_all("/(?:[\\x00-\x7F]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2}){1,50}/", $v, $b);
                            $v = implode('', $b[0]);
/**/                    }
/**/                }
                }
            }

            reset($a[$i]);
            unset($a[$i]);
        }

        unset($a, $v);
/**/}


/**/$a = md5(mt_rand());
/**/$b = @ini_set('display_errors', $a);
/**/
/**/if (@ini_get('display_errors') !== $a)
/**/{
/**/    /*<*/patchwork_bootstrapper::alias('ini_set',        'patchwork_ini_set', array('$k', '$v'))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('ini_alter',      'patchwork_ini_set', array('$k', '$v'))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('ini_get',        'patchwork_ini_get', array('$k'))/*>*/;
/**/    /*<*/patchwork_bootstrapper::alias('set_time_limit', 'patchwork_set_time_limit', array('$s'))/*>*/;

        function patchwork_ini_set($k, $v)    {return @ini_set($k, $v);}
        function patchwork_ini_get($k)        {return @ini_get($k);}
        function patchwork_set_time_limit($s) {return @set_time_limit($s);}
/**/}
/**/else if (ini_get_bool('safe_mode'))
/**/{
/**/    /*<*/patchwork_bootstrapper::alias('set_time_limit', 'patchwork_set_time_limit', array('$s'))/*>*/;
        function patchwork_set_time_limit($a) {return @set_time_limit($s);}
/**/}
/**/
/**/@ini_set('display_errors', $b);


// Ease transition to patchwork-next

class Patchwork_Utf8
{
    static function toASCII($s)
    {
        return patchwork::toASCII($s);
    }
}

class Patchwork_Superloader
{
    static function class2file($class) {return patchwork_class2file($class);}
    static function file2class($file) {return patchwork_file2class($file);}
}

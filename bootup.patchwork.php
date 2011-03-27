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
$patchwork_appId = (int) /*<*/sprintf('%020d', Patchwork_Bootstrapper::$appId)/*>*/;
$_REQUEST = array(); // $_REQUEST is an open door to security problems.


// Basic aliasing

/**/ /*<*/Patchwork_Bootstrapper::alias('w',          'trigger_error', array('$msg', '$type' => E_USER_NOTICE))/*>*/;
/**/ /*<*/Patchwork_Bootstrapper::alias('rand',       'mt_rand',       array('$min' => 0, '$max' => mt_getrandmax()))/*>*/;
/**/ /*<*/Patchwork_Bootstrapper::alias('getrandmax', 'mt_getrandmax', array())/*>*/;


// Aliases to backport namespaces to PHP pre-5.3

/**/if (PHP_VERSION_ID < 50300)
/**/{
/**/    /*<*/Patchwork_Bootstrapper::alias('class_implements',        'Patchwork_PHP_Overlay_Class::class_implements',        array('$class', '$autoload' => true))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('class_parents',           'Patchwork_PHP_Overlay_Class::class_parents',           array('$class', '$autoload' => true))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('class_exists',            'Patchwork_PHP_Overlay_Class::class_exists',            array('$class', '$autoload' => true))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('get_class_methods',       'Patchwork_PHP_Overlay_Class::get_class_methods',       array('$class'))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('get_class_vars',          'Patchwork_PHP_Overlay_Class::get_class_vars',          array('$class'))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('get_class',               'Patchwork_PHP_Overlay_Class::get_class',               array('$obj'))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('get_declared_classes',    'Patchwork_PHP_Overlay_Class::get_declared_classes',    array())/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('get_declared_interfaces', 'Patchwork_PHP_Overlay_Class::get_declared_interfaces', array())/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('get_parent_class',        'Patchwork_PHP_Overlay_Class::get_parent_class',        array('$class'))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('interface_exists',        'Patchwork_PHP_Overlay_Class::interface_exists',        array('$class', '$autoload' => true))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('is_a',                    'Patchwork_PHP_Overlay_Class::is_a',                    array('$obj', '$class'))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('is_subclass_of',          'Patchwork_PHP_Overlay_Class::is_subclass_of',          array('$obj', '$class'))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('method_exists',           'Patchwork_PHP_Overlay_Class::method_exists',           array('$class', '$method'))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('property_exists',         'Patchwork_PHP_Overlay_Class::property_exists',         array('$class', '$property'))/*>*/;
/**/}

/**/if (!function_exists('spl_object_hash'))
/**/{
/**/    /*<*/Patchwork_Bootstrapper::alias('spl_object_hash',   'Patchwork_PHP_Overlay_Class::spl_object_hash',   array('$object'))/*>*/;
/**/}


// Changing default charset to UTF-8, adding new $double_encode parameter (since 5.2.3)

/**/ /*<*/Patchwork_Bootstrapper::alias('html_entity_decode', 'html_entity_decode', array('$s', '$style' => ENT_COMPAT, '$charset' => 'UTF-8'))/*>*/;

/**/if (PHP_VERSION_ID < 50203)
/**/{
/**/    /*<*/Patchwork_Bootstrapper::alias('htmlspecialchars', 'Patchwork_PHP_Overlay_Strings::htmlspecialchars', array('$s', '$style' => ENT_COMPAT, '$charset' => 'UTF-8', '$double_enc' => true))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('htmlentities',     'Patchwork_PHP_Overlay_Strings::htmlentities',     array('$s', '$style' => ENT_COMPAT, '$charset' => 'UTF-8', '$double_enc' => true))/*>*/;
/**/}
/**/else
/**/{
/**/    // No alias for htmlspecialchars() because ISO-8859-1 and UTF-8 are both compatible with ASCII, where the HTML_SPECIALCHARS table lies
/**/    /*<*/Patchwork_Bootstrapper::alias('htmlentities', 'htmlentities', array('$s', '$style' => ENT_COMPAT, '$charset' => 'UTF-8', '$double_enc' => true))/*>*/;
/**/}


// Fix 5.2.9 array_unique() default sort flag
/**/if (PHP_VERSION_ID == 50209)
/**/    /*<*/Patchwork_Bootstrapper::alias('array_unique', 'array_unique', array('$array', '$sort_flags' => SORT_STRING))/*>*/;

// Workaround http://bugs.php.net/37394
/**/if (PHP_VERSION_ID < 50200)
/**/    /*<*/Patchwork_Bootstrapper::alias('substr_compare', 'Patchwork_PHP_Overlay_Strings::substr_compare', array('$main_str', '$str', '$offset', '$length' => INF, '$case_insensitivity' => false))/*>*/;

// Backport $httpOnly parameter
/**/if (PHP_VERSION_ID < 50200)
/**/{
/**/    $a = array('$name', '$value' => '', '$expires' => 0, '$path' => '', '$domain' => '', '$secure' => false, '$httponly' => false);
/**/    /*<*/Patchwork_Bootstrapper::alias('setcookie',    'Patchwork_PHP_Overlay_Strings::setcookie',    $a)/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('setcookieraw', 'Patchwork_PHP_Overlay_Strings::setcookieraw', $a)/*>*/;
/**/}


// mbstring configuration

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

/**/    if (MB_OVERLOAD_MAIL & (int) @ini_get('mbstring.func_overload'))
/**/    {
/**/        /*<*/Patchwork_Bootstrapper::alias('mail', 'Patchwork_PHP_Overlay_Mbstring8bit::mail', array('$to', '$subject', '$message', '$headers' => '', '$params' => ''))/*>*/;
/**/    }
/**/
/**/    if (MB_OVERLOAD_STRING & (int) @ini_get('mbstring.func_overload'))
/**/    {
/**/        /*<*/Patchwork_Bootstrapper::alias('strlen',  'Patchwork_PHP_Overlay_Mbstring8bit::strlen',  array('$s'))/*>*/;
/**/        /*<*/Patchwork_Bootstrapper::alias('strpos',  'Patchwork_PHP_Overlay_Mbstring8bit::strpos',  array('$s', '$needle', '$offset' => 0))/*>*/;
/**/        /*<*/Patchwork_Bootstrapper::alias('strrpos', 'Patchwork_PHP_Overlay_Mbstring8bit::strrpos', array('$s', '$needle', '$offset' => 0))/*>*/;
/**/        /*<*/Patchwork_Bootstrapper::alias('substr',  'Patchwork_PHP_Overlay_Mbstring8bit::substr',  array('$s', '$start', '$length' => INF))/*>*/;
/**/
/**/        if (PHP_VERSION_ID >= 50200)
/**/        {
/**/            /*<*/Patchwork_Bootstrapper::alias('stripos',  'Patchwork_PHP_Overlay_Mbstring8bit::stripos',  array('$s', '$needle', '$offset' => 0,   '$enc' => INF))/*>*/;
/**/            /*<*/Patchwork_Bootstrapper::alias('stristr',  'Patchwork_PHP_Overlay_Mbstring8bit::stristr',  array('$s', '$needle', '$part' => false, '$enc' => INF))/*>*/;
/**/            /*<*/Patchwork_Bootstrapper::alias('strrchr',  'Patchwork_PHP_Overlay_Mbstring8bit::strrchr',  array('$s', '$needle', '$part' => false, '$enc' => INF))/*>*/;
/**/            /*<*/Patchwork_Bootstrapper::alias('strripos', 'Patchwork_PHP_Overlay_Mbstring8bit::strripos', array('$s', '$needle', '$offset' => 0,   '$enc' => INF))/*>*/;
/**/            /*<*/Patchwork_Bootstrapper::alias('strstr',   'Patchwork_PHP_Overlay_Mbstring8bit::strstr',   array('$s', '$needle', '$part' => false, '$enc' => INF))/*>*/;
/**/        }
/**/    }
/**/}
/**/else
/**/{
        define('MB_OVERLOAD_MAIL',   1);
        define('MB_OVERLOAD_STRING', 2);
        define('MB_OVERLOAD_REGEX',  4);
        define('MB_CASE_UPPER', 0);
        define('MB_CASE_LOWER', 1);
        define('MB_CASE_TITLE', 2);

/**/    /*<*/Patchwork_Bootstrapper::alias('mb_convert_encoding',     'Patchwork_PHP_Overlay_Mbstring50::convert_encoding',  array('$s', '$to', '$from' => INF))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('mb_decode_mimeheader',    'Patchwork_PHP_Overlay_Mbstring50::decode_mimeheader', array('$s'))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('mb_encode_mimeheader',    'Patchwork_PHP_Overlay_Mbstring50::convert_case',      array('$s', '$charset' => INF, '$transfer_enc' => INF, '$lf' => INF, '$indent' => INF))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('mb_convert_case',         'Patchwork_PHP_Overlay_Mbstring50::convert_case',      array('$s', '$mode', '$enc' => INF))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('mb_internal_encoding',    'Patchwork_PHP_Overlay_Mbstring50::internal_encoding', array('$enc' => INF))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('mb_list_encodings',       'Patchwork_PHP_Overlay_Mbstring50::list_encodings',    array())/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('mb_parse_str',            'parse_str',                                           array('$s', '&$result' => array()))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('mb_strlen',               'Patchwork_PHP_Overlay_Mbstring50::strlen',            array('$s', '$enc' => INF))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('mb_strpos',               'Patchwork_PHP_Overlay_Mbstring50::strpos',            array('$s', '$needle', '$offset' => 0, '$enc' => INF))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('mb_strtolower',           'Patchwork_PHP_Overlay_Mbstring50::strtolower',        array('$s', '$enc' => INF))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('mb_strtoupper',           'Patchwork_PHP_Overlay_Mbstring50::strtoupper',        array('$s', '$enc' => INF))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('mb_substitute_character', 'Patchwork_PHP_Overlay_Mbstring50::substitute_character', array('$char' => INF))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('mb_substr_count',         'substr_count',                                        array('$s',  '$needle'))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('mb_substr',               'Patchwork_PHP_Overlay_Mbstring50::substr',            array('$s', '$start', '$length' => PHP_INT_MAX, '$enc' => INF))/*>*/;
/**/}

/**/if (!extension_loaded('mbstring') || PHP_VERSION_ID < 50200)
/**/{
/**/    /*<*/Patchwork_Bootstrapper::alias('mb_stripos',  'Patchwork_PHP_Overlay_Mbstring52::stripos',  array('$s', '$needle', '$offset' => 0,   '$enc' => INF))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('mb_stristr',  'Patchwork_PHP_Overlay_Mbstring52::stristr',  array('$s', '$needle', '$part' => false, '$enc' => INF))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('mb_strrchr',  'Patchwork_PHP_Overlay_Mbstring52::strrchr',  array('$s', '$needle', '$part' => false, '$enc' => INF))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('mb_strrichr', 'Patchwork_PHP_Overlay_Mbstring52::strrichr', array('$s', '$needle', '$part' => false, '$enc' => INF))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('mb_strripos', 'Patchwork_PHP_Overlay_Mbstring52::strripos', array('$s', '$needle', '$offset' => 0,   '$enc' => INF))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('mb_strrpos',  'Patchwork_PHP_Overlay_Mbstring52::strrpos',  array('$s', '$needle', '$offset' => 0,   '$enc' => INF))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('mb_strstr',   'Patchwork_PHP_Overlay_Mbstring52::strstr',   array('$s', '$needle', '$part' => false, '$enc' => INF))/*>*/;
/**/
/**/    /*<*/Patchwork_Bootstrapper::alias('mb_strrpos50', extension_loaded('mbstring') ? 'mb_strrpos' : 'Patchwork_PHP_Overlay_Mbstring50::strrpos', array('$s', '$needle', '$enc' => INF))/*>*/;
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
/**/    /*<*/Patchwork_Bootstrapper::alias('basename', 'Patchwork_PHP_Overlay_Fs::basename', array('$path', '$suffix' => ''))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('pathinfo', 'Patchwork_PHP_Overlay_Fs::pathinfo', array('$path', '$option' => INF))/*>*/;
/**/}


// Class ob: wrapper for ob_start()

/**/ /*<*/Patchwork_Bootstrapper::alias('ob_start', 'ob::start', array('$callback' => null, '$chunk_size' => null, '$erase' => true))/*>*/;

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
/**/    /*<*/Patchwork_Bootstrapper::alias('iconv', 'libiconv', array('$from', '$to', '$s'))/*>*/;
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
        define('ICONV_IMPL', 'Patchwork');
        define('ICONV_VERSION', '1.0');
        define('ICONV_MIME_DECODE_STRICT', 1);
        define('ICONV_MIME_DECODE_CONTINUE_ON_ERROR', 2);

/**/    /*<*/Patchwork_Bootstrapper::alias('iconv', 'Patchwork_PHP_Overlay_Iconv::iconv', array('$from', '$to', '$s'))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('iconv_get_encoding', 'Patchwork_PHP_Overlay_Iconv::get_encoding', array('$type' => 'all'))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('iconv_set_encoding', 'Patchwork_PHP_Overlay_Iconv::set_encoding', array('$type', '$charset'))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('iconv_mime_encode',  'Patchwork_PHP_Overlay_Iconv::mime_encode',  array('$name', '$value', '$pref' => INF))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('ob_iconv_handler',   'Patchwork_PHP_Overlay_Iconv::ob_handler',   array('$buffer', '$mode'))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('iconv_mime_decode_headers', 'Patchwork_PHP_Overlay_Iconv::mime_decode_headers', array('$encoded_headers', '$mode' => 2, '$charset' => INF))/*>*/;
/**/
/**/    if (extension_loaded('mbstring'))
/**/    {
/**/        /*<*/Patchwork_Bootstrapper::alias('iconv_strlen',  'mb_strlen',  array('$s', '$enc' => INF))/*>*/;
/**/        /*<*/Patchwork_Bootstrapper::alias('iconv_strpos',  'mb_strpos',  array('$s', '$needle', '$offset' => 0, '$enc' => INF))/*>*/;
/**/        /*<*/Patchwork_Bootstrapper::alias('iconv_strrpos', 'mb_strrpos', array('$s', '$needle',                 '$enc' => INF))/*>*/;
/**/        /*<*/Patchwork_Bootstrapper::alias('iconv_substr',  'mb_substr',  array('$s', '$start', '$length' => PHP_INT_MAX, '$enc' => INF))/*>*/;
/**/        /*<*/Patchwork_Bootstrapper::alias('iconv_mime_decode', 'mb_decode_mimeheader', array('$encoded_headers', '$mode' => 2, '$charset' => INF))/*>*/;
/**/    }
/**/    else
/**/    {
/**/        extension_loaded('xml')
/**/            ? /*<*/Patchwork_Bootstrapper::alias('iconv_strlen', 'Patchwork_PHP_Overlay_Iconv::strlen1', array('$s', '$enc' => INF))/*>*/
/**/            : /*<*/Patchwork_Bootstrapper::alias('iconv_strlen', 'Patchwork_PHP_Overlay_Iconv::strlen2', array('$s', '$enc' => INF))/*>*/;
/**/
/**/        /*<*/Patchwork_Bootstrapper::alias('iconv_strpos',  'Patchwork_PHP_Overlay_Mbstring52::strpos',  array('$s', '$needle', '$offset' => 0, '$enc' => INF))/*>*/;
/**/        /*<*/Patchwork_Bootstrapper::alias('iconv_strrpos', 'Patchwork_PHP_Overlay_Mbstring52::strrpos', array('$s', '$needle',                 '$enc' => INF))/*>*/;
/**/        /*<*/Patchwork_Bootstrapper::alias('iconv_substr',  'Patchwork_PHP_Overlay_Mbstring52::substr',  array('$s', '$start', '$length' => PHP_INT_MAX, '$enc' => INF))/*>*/;
/**/        /*<*/Patchwork_Bootstrapper::alias('iconv_mime_decode',  'Patchwork_PHP_Overlay_Iconv::mime_decode', array('$encoded_headers', '$mode' => 2, '$charset' => INF))/*>*/;
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

/**/ /*<*/Patchwork_Bootstrapper::alias('utf8_encode', 'Patchwork_PHP_Overlay_Strings::utf8_encode', array('$s'))/*>*/;
/**/ /*<*/Patchwork_Bootstrapper::alias('utf8_decode', 'Patchwork_PHP_Overlay_Strings::utf8_decode', array('$s'))/*>*/;


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
/**/    /*<*/Patchwork_Bootstrapper::alias('normalizer_is_normalized', 'Normalizer::isNormalized', array('$s', '$form' => 'NFC'))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('normalizer_normalize',     'Normalizer::normalize',    array('$s', '$form' => 'NFC'))/*>*/;
/**/
/**/    /*<*/Patchwork_Bootstrapper::alias('grapheme_stripos',  'Patchwork_PHP_Overlay_Intl::stripos',  array('$s', '$needle', '$offset' => 0))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('grapheme_stristr',  'Patchwork_PHP_Overlay_Intl::stristr',  array('$s', '$needle', '$before_needle' => false))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('grapheme_strlen',   'Patchwork_PHP_Overlay_Intl::strlen',   array('$s'))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('grapheme_strpos',   'Patchwork_PHP_Overlay_Intl::strpos',   array('$s', '$needle', '$offset' => 0))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('grapheme_strripos', 'Patchwork_PHP_Overlay_Intl::strripos', array('$s', '$needle', '$offset' => 0))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('grapheme_strrpos',  'Patchwork_PHP_Overlay_Intl::strrpos',  array('$s', '$needle', '$offset' => 0))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('grapheme_strstr',   'Patchwork_PHP_Overlay_Intl::strstr',   array('$s', '$needle', '$before_needle' => false))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('grapheme_substr',   'Patchwork_PHP_Overlay_Intl::substr',   array('$s', '$start', '$len' => INF))/*>*/;
/**/}


// Workaround for http://bugs.php.net/33140

/**/if ('\\' === DIRECTORY_SEPARATOR && PHP_VERSION_ID < 50200)
/**/{
/**/    /*<*/Patchwork_Bootstrapper::alias('mkdir', 'patchwork_mkdir', array('$pathname', '$mode' => 0777, '$recursive' => false, '$context' => INF))/*>*/;

        function patchwork_mkdir($pathname, $mode = 0777, $recursive = false, $context = INF)
        {
            return INF === $context
                ? mkdir(strtr($pathname, '/', '\\'), $mode, $recursive)
                : mkdir($pathname, $mode, $recursive, $context);
        }
/**/}


// Default serialize precision is 100, but 17 is enough

/**/if (17 != @ini_get('serialize_precision'))
        @ini_set('serialize_precision', 17);


/**/$a = file_get_contents(Patchwork_Bootstrapper::$pwd . 'data/utf8/quickChecks.txt');
/**/$a = explode("\n", $a);
define('UTF8_NFC_RX',            /*<*/'/' . $a[1] . '/u'/*>*/);
define('PATCHWORK_PROJECT_PATH', /*<*/Patchwork_Bootstrapper::$cwd   /*>*/);
define('PATCHWORK_ZCACHE',       /*<*/Patchwork_Bootstrapper::$zcache/*>*/);
define('PATCHWORK_PATH_LEVEL',   /*<*/Patchwork_Bootstrapper::$last  /*>*/);
define('PATCHWORK_PATH_OFFSET',  /*<*/count(Patchwork_Bootstrapper::$paths) - Patchwork_Bootstrapper::$last/*>*/);

$patchwork_path = /*<*/Patchwork_Bootstrapper::$paths/*>*/;
$_patchwork_abstract = array();
$_patchwork_destruct = array();
$CONFIG = array();


// Utility functions

function patchwork_include($file) {return include $file;}
function &patchwork_autoload_marker($marker, &$ref) {return $ref;}
function strlencmp($a, $b) {return strlen($b) - strlen($a);}

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
        call_user_func(array(array_shift($GLOBALS['_patchwork_destruct']), '__destructStatic'));
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

function patchwork_class2cache($class, $level)
{
    if (false !== strpos($class, '__x'))
    {
        static $map = array(
            array('__x25', '__x2B', '__x2D', '__x2E', '__x3D', '__x7E'),
            array('%',     '+',     '-',     '.',     '=',     '~'    )
        );

        $class = str_replace($map[0], $map[1], $class);
    }

    $cache = (int) DEBUG . (0>$level ? -$level . '-' : $level);
    $cache = /*<*/Patchwork_Bootstrapper::$cwd . '.class_'/*>*/
            . strtr($class, '\\', '_') . ".{$cache}.zcache.php";

    return $cache;
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
        $level = /*<*/Patchwork_Bootstrapper::$last/*>*/;
    }
    else
    {
        0 <= $level && $base = 0;
        $i = /*<*/Patchwork_Bootstrapper::$last/*>*/ - $level - $base;
        0 > $i && $i = 0;
    }

/**/if (IS_WINDOWS)
        false !== strpos($file, '\\') && $file = strtr($file, '\\', '/');

    if (0 === $i)
    {
        $source = /*<*/Patchwork_Bootstrapper::$cwd/*>*/ . $file;

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


/**/if ($a = Patchwork_Bootstrapper::updatedb())
/**/{
        static $db;

        if (!isset($db))
        {
            if (!$db = @dba_popen(/*<*/Patchwork_Bootstrapper::$cwd . '.patchwork.paths.db'/*>*/, 'rd', /*<*/$a/*>*/))
            {
                require_once /*<*/Patchwork_Bootstrapper::$pwd . 'class/Patchwork/Bootstrapper.php'/*>*/;

                $db = Patchwork_Bootstrapper::fixParentPaths(/*<*/Patchwork_Bootstrapper::$pwd/*>*/);
            }
        }

        $base = dba_fetch($file, $db);
/**/}
/**/else
/**/{
        $base = md5($file);
        $base = /*<*/Patchwork_Bootstrapper::$zcache/*>*/ . $base[0] . '/' . $base[1] . '/' . substr($base, 2) . '.path.txt';
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

function url_enc_utf8_dec_callback($m) {return urlencode(Patchwork_PHP_Overlay_Strings::utf8_encode(urldecode($m[0])));}

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
/**/    /*<*/Patchwork_Bootstrapper::alias('ini_set',        'patchwork_ini_set', array('$k', '$v'))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('ini_alter',      'patchwork_ini_set', array('$k', '$v'))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('ini_get',        'patchwork_ini_get', array('$k'))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('set_time_limit', 'patchwork_set_time_limit', array('$s'))/*>*/;

        function patchwork_ini_set($k, $v)    {return @ini_set($k, $v);}
        function patchwork_ini_get($k)        {return @ini_get($k);}
        function patchwork_set_time_limit($s) {return @set_time_limit($s);}
/**/}
/**/else if (ini_get_bool('safe_mode'))
/**/{
/**/    /*<*/Patchwork_Bootstrapper::alias('set_time_limit', 'patchwork_set_time_limit', array('$s'))/*>*/;
        function patchwork_set_time_limit($a) {return @set_time_limit($s);}
/**/}
/**/
/**/@ini_set('display_errors', $b);


// Setup class loading mechanism

@ini_set('unserialize_callback_func', 'spl_autoload_call');

/**/if (function_exists('__autoload'))
/**/{
/**/    if (!function_exists('spl_autoload_register'))
/**/    {
            // Trigger a "Cannot redeclare" fatal error: autoloading is already locked
            function __autoload($class) {}
/**/    }

        spl_autoload_register('__autoload');
/**/}

/**/if (PHP_VERSION_ID < 50300 || !function_exists('spl_autoload_register'))
/**/{
/**/    // Before PHP 5.3, backport spl_autoload_register()'s $prepend argument
/**/    // and workaround http://bugs.php.net/44144
/**/
/**/    /*<*/Patchwork_Bootstrapper::alias('__autoload',              'Patchwork_PHP_Overlay_SplAutoload::call',       array('$class'))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('spl_autoload_call',       'Patchwork_PHP_Overlay_SplAutoload::call',       array('$class'))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('spl_autoload_functions',  'Patchwork_PHP_Overlay_SplAutoload::functions',  array())/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('spl_autoload_register',   'Patchwork_PHP_Overlay_SplAutoload::register',   array('$callback', '$throw' => true, '$prepend' => false))/*>*/;
/**/    /*<*/Patchwork_Bootstrapper::alias('spl_autoload_unregister', 'Patchwork_PHP_Overlay_SplAutoload::unregister', array('$callback'))/*>*/;

/**/    @unlink(Patchwork_Bootstrapper::$cwd . '.patchwork.spl-autoload.php');
/**/    copy(Patchwork_Bootstrapper::$pwd . 'class/Patchwork/PHP/Overlay/SplAutoload.php', Patchwork_Bootstrapper::$cwd . '.patchwork.spl-autoload.php');

        require /*<*/Patchwork_Bootstrapper::$cwd . '.patchwork.spl-autoload.php'/*>*/;
/**/}
/**/else
/**/{
/**/    /*<*/Patchwork_Bootstrapper::alias('__autoload', 'spl_autoload_call', array('$class'))/*>*/;
/**/}


// patchwork_autoload(): the magic part

/**/@unlink(Patchwork_Bootstrapper::$cwd . '.patchwork.autoloader.php');
/**/copy(Patchwork_Bootstrapper::$pwd . 'class/Patchwork/Autoloader.php', Patchwork_Bootstrapper::$cwd . '.patchwork.autoloader.php');
/**/win_hide_file(Patchwork_Bootstrapper::$cwd . '.patchwork.autoloader.php');

spl_autoload_register('patchwork_autoload');

function patchwork_is_autoloaded($class, $autoload = false)
{
    if (class_exists($class, $autoload) || interface_exists($class, false)) return true;

/**/if (function_exists('class_alias'))
/**/{
        $a = strtr($class, '\\', '_');

        if (class_exists($a, false) || interface_exists($a, false))
        {
            class_alias($a, $class);
            return true;
        }
/**/}

    return false;
}

function patchwork_autoload($class)
{
    if (patchwork_is_autoloaded($class)) return;

    $a = strtolower(strtr($class, '\\', ''));

    if ($a !== strtr($a, ";'?.$", '-----')) return;

    if (TURBO && $a =& $GLOBALS["c\x9D"][$a])
    {
        if (is_int($a))
        {
            $b = $a;
            unset($a);
            $a = $b - /*<*/count(Patchwork_Bootstrapper::$paths) - Patchwork_Bootstrapper::$last/*>*/;

            $b = strtr($class, '\\', '_');
            $i = strrpos($b, '__');
            false !== $i && isset($b[$i+2]) && '' === trim(substr($b, $i+2), '0123456789') && $b = substr($b, 0, $i);

            $a = $b . '.php.' . DEBUG . (0>$a ? -$a . '-' : $a);
        }

        $a = /*<*/Patchwork_Bootstrapper::$cwd/*>*/ . ".class_{$a}.zcache.php";

        $GLOBALS["a\x9D"] = false;

        if (file_exists($a))
        {
            patchwork_include($a);

            if (patchwork_is_autoloaded($class)) return;
        }
    }

    if (!class_exists('Patchwork_Autoloader', false))
    {
        require /*<*/Patchwork_Bootstrapper::$cwd . '.patchwork.autoloader.php'/*>*/;
    }

    Patchwork_Autoloader::autoload($class);
}


// patchworkProcessedPath(): private use for the preprocessor (in files in the include_path)

function patchworkProcessedPath($file, $lazy = false)
{
/**/if (IS_WINDOWS)
        false !== strpos($file, '\\') && $file = strtr($file, '\\', '/');

    if (false !== strpos('.' . $file, './') || (/*<*/IS_WINDOWS/*>*/ && ':' === substr($file, 1, 1)))
    {
/**/if (function_exists('__patchwork_realpath'))
        if ($f = patchwork_realpath($file)) $file = $f;
/**/else
        if ($f = realpath($file)) $file = $f;

        $p =& $GLOBALS['patchwork_path'];

        for ($i = /*<*/Patchwork_Bootstrapper::$last + 1/*>*/; $i < /*<*/count(Patchwork_Bootstrapper::$paths)/*>*/; ++$i)
        {
            if (0 === strncmp($file, $p[$i], strlen($p[$i])))
            {
                $file = substr($file, strlen($p[$i]));
                break;
            }
        }

        if (/*<*/count(Patchwork_Bootstrapper::$paths)/*>*/ === $i) return $f;
    }

    $source = patchworkPath('class/' . $file, $level);

    if (false === $source) return false;

    $cache = patchwork_file2class($file);
    $cache = patchwork_class2cache($cache, $level);

    if (file_exists($cache) && (TURBO || filemtime($cache) > filemtime($source))) return $cache;

    Patchwork_Preprocessor::execute($source, $cache, $level, false, true, $lazy);

    return $cache;
}


// PHP session mechanism overloading

class sessionHandler implements ArrayAccess
{
    function offsetGet($k)     {$_SESSION = SESSION::getAll(); return $_SESSION[$k];}
    function offsetSet($k, $v) {$_SESSION = SESSION::getAll(); $_SESSION[$k] =& $v;}
    function offsetExists($k)  {$_SESSION = SESSION::getAll(); return isset($_SESSION[$k]);}
    function offsetUnset($k)   {$_SESSION = SESSION::getAll(); unset($_SESSION[$k]);}

    static $id;

    static function close()   {return true;}
    static function gc($life) {return true;}

    static function open($path, $name)
    {
        session_cache_limiter('');
        ini_set('session.use_only_cookies', true);
        ini_set('session.use_cookies', false);
        ini_set('session.use_trans_sid', false);
        return true;
    }

    static function read($id)
    {
        $_SESSION = SESSION::getAll();
        self::$id = $id;
        return '';
    }

    static function write($id, $data)
    {
        if (self::$id != $id) SESSION::regenerateId();
        return true;
    }

    static function destroy($id)
    {
        SESSION::regenerateId(true);
        return true;
    }
}

session_set_save_handler(
    array($k = 'sessionHandler', 'open'),
    array($k, 'close'),
    array($k, 'read'),
    array($k, 'write'),
    array($k, 'destroy'),
    array($k, 'gc')
);

$_SESSION = new sessionHandler;

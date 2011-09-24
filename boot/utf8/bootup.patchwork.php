<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
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


// basename() and pathinfo() are locale sensitive, but this breaks UTF-8 paths

/**/if ('' === basename('§'))
/**/{
/**/    /*<*/boot::$manager->override('basename', ':Fs:', array('$path', '$suffix' => ''))/*>*/;
/**/    /*<*/boot::$manager->override('pathinfo', ':Fs:', array('$path', '$option' => INF))/*>*/;
/**/}


// Changing default charset to UTF-8, adding new $double_encode parameter (since 5.2.3)

/**/ /*<*/boot::$manager->override('html_entity_decode', 'html_entity_decode', array('$s', '$style' => ENT_COMPAT, '$charset' => 'UTF-8'))/*>*/;

/**/if (PHP_VERSION_ID < 50203)
/**/{
/**/    /*<*/boot::$manager->override('htmlspecialchars', ':Utf8:', array('$s', '$style' => ENT_COMPAT, '$charset' => 'UTF-8', '$double_enc' => true))/*>*/;
/**/    /*<*/boot::$manager->override('htmlentities',     ':Utf8:', array('$s', '$style' => ENT_COMPAT, '$charset' => 'UTF-8', '$double_enc' => true))/*>*/;
/**/}
/**/else
/**/{
/**/    // No override for htmlspecialchars() because ISO-8859-1 and UTF-8 are both compatible with ASCII, where the HTML_SPECIALCHARS table lies
/**/    /*<*/boot::$manager->override('htmlentities', 'htmlentities', array('$s', '$style' => ENT_COMPAT, '$charset' => 'UTF-8', '$double_enc' => true))/*>*/;
/**/}


// mbstring configuration

/**/if (extension_loaded('mbstring'))
/**/{
/**/    if (ini_get_bool('mbstring.encoding_translation') && !in_array(strtolower(ini_get('mbstring.http_input')), array('pass', '8bit', 'utf-8')))
/**/        throw boot::$manager->error('Please disable "mbstring.encoding_translation" or set "mbstring.http_input" to "pass" or "utf-8"');

        mb_regex_encoding('UTF-8');
        ini_set('mbstring.script_encoding', 'pass');

/**/    if ('utf-8' !== strtolower(mb_internal_encoding()))
            mb_internal_encoding('UTF-8')   + ini_set('mbstring.internal_encoding', 'UTF-8');

/**/    if ('none'  !== strtolower(mb_substitute_character()))
            mb_substitute_character('none') + ini_set('mbstring.substitute_character', 'none');

/**/    if (!in_array(strtolower(mb_http_output()), array('pass', '8bit')))
            mb_http_output('pass')          + ini_set('mbstring.http_output', 'pass');

/**/    if (!in_array(strtolower(mb_language()), array('uni', 'neutral')))
            mb_language('uni')              + ini_set('mbstring.language', 'uni');

/**/    if (MB_OVERLOAD_MAIL & (int) ini_get('mbstring.func_overload'))
/**/    {
/**/        /*<*/boot::$manager->override('mail', ':Mbstring8bit:', array('$to', '$subject', '$message', '$headers' => '', '$params' => ''))/*>*/;
/**/    }
/**/
/**/    if (MB_OVERLOAD_STRING & (int) ini_get('mbstring.func_overload'))
/**/    {
/**/        /*<*/boot::$manager->override('strlen',   ':Mbstring8bit:', array('$s'))/*>*/;
/**/        /*<*/boot::$manager->override('strpos',   ':Mbstring8bit:', array('$s', '$needle', '$offset' => 0))/*>*/;
/**/        /*<*/boot::$manager->override('strrpos',  ':Mbstring8bit:', array('$s', '$needle', '$offset' => 0))/*>*/;
/**/        /*<*/boot::$manager->override('substr',   ':Mbstring8bit:', array('$s', '$start', '$length' => INF))/*>*/;
/**/        /*<*/boot::$manager->override('stripos',  ':Mbstring8bit:', array('$s', '$needle', '$offset' => 0,   '$enc' => INF))/*>*/;
/**/        /*<*/boot::$manager->override('stristr',  ':Mbstring8bit:', array('$s', '$needle', '$part' => false, '$enc' => INF))/*>*/;
/**/        /*<*/boot::$manager->override('strrchr',  ':Mbstring8bit:', array('$s', '$needle', '$part' => false, '$enc' => INF))/*>*/;
/**/        /*<*/boot::$manager->override('strripos', ':Mbstring8bit:', array('$s', '$needle', '$offset' => 0,   '$enc' => INF))/*>*/;
/**/        /*<*/boot::$manager->override('strstr',   ':Mbstring8bit:', array('$s', '$needle', '$part' => false, '$enc' => INF))/*>*/;
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

/**/    /*<*/boot::$manager->override('mb_convert_encoding',     ':Mbstring50:', array('$s', '$to', '$from' => INF))/*>*/;
/**/    /*<*/boot::$manager->override('mb_decode_mimeheader',    ':Mbstring50:', array('$s'))/*>*/;
/**/    /*<*/boot::$manager->override('mb_encode_mimeheader',    ':Mbstring50:', array('$s', '$charset' => INF, '$transfer_enc' => INF, '$lf' => INF, '$indent' => INF))/*>*/;
/**/    /*<*/boot::$manager->override('mb_convert_case',         ':Mbstring50:', array('$s', '$mode', '$enc' => INF))/*>*/;
/**/    /*<*/boot::$manager->override('mb_internal_encoding',    ':Mbstring50:', array('$enc' => INF))/*>*/;
/**/    /*<*/boot::$manager->override('mb_list_encodings',       ':Mbstring50:', array())/*>*/;
/**/    /*<*/boot::$manager->override('mb_parse_str',            'parse_str',    array('$s', '&$result' => array()))/*>*/;
/**/    /*<*/boot::$manager->override('mb_strlen',               ':Mbstring50:', array('$s', '$enc' => INF))/*>*/;
/**/    /*<*/boot::$manager->override('mb_strpos',               ':Mbstring50:', array('$s', '$needle', '$offset' => 0, '$enc' => INF))/*>*/;
/**/    /*<*/boot::$manager->override('mb_strtolower',           ':Mbstring50:', array('$s', '$enc' => INF))/*>*/;
/**/    /*<*/boot::$manager->override('mb_strtoupper',           ':Mbstring50:', array('$s', '$enc' => INF))/*>*/;
/**/    /*<*/boot::$manager->override('mb_substitute_character', ':Mbstring50:', array('$char' => INF))/*>*/;
/**/    /*<*/boot::$manager->override('mb_substr_count',         'substr_count', array('$s',  '$needle'))/*>*/;
/**/    /*<*/boot::$manager->override('mb_substr',               ':Mbstring50:', array('$s', '$start', '$length' => PHP_INT_MAX, '$enc' => INF))/*>*/;
/**/    /*<*/boot::$manager->override('mb_stripos',              ':Mbstring52:', array('$s', '$needle', '$offset' => 0,   '$enc' => INF))/*>*/;
/**/    /*<*/boot::$manager->override('mb_stristr',              ':Mbstring52:', array('$s', '$needle', '$part' => false, '$enc' => INF))/*>*/;
/**/    /*<*/boot::$manager->override('mb_strrchr',              ':Mbstring52:', array('$s', '$needle', '$part' => false, '$enc' => INF))/*>*/;
/**/    /*<*/boot::$manager->override('mb_strrichr',             ':Mbstring52:', array('$s', '$needle', '$part' => false, '$enc' => INF))/*>*/;
/**/    /*<*/boot::$manager->override('mb_strripos',             ':Mbstring52:', array('$s', '$needle', '$offset' => 0,   '$enc' => INF))/*>*/;
/**/    /*<*/boot::$manager->override('mb_strrpos',              ':Mbstring52:', array('$s', '$needle', '$offset' => 0,   '$enc' => INF))/*>*/;
/**/    /*<*/boot::$manager->override('mb_strstr',               ':Mbstring52:', array('$s', '$needle', '$part' => false, '$enc' => INF))/*>*/;
/**/
/**/    /*<*/boot::$manager->override('mb_strrpos50', extension_loaded('mbstring') ? 'mb_strrpos' : ':Mbstring50:', array('$s', '$needle', '$enc' => INF))/*>*/;
/**/}


// iconv configuration

/**/ // See http://php.net/manual/en/function.iconv.php#47428
/**/if (!function_exists('iconv') && function_exists('libiconv'))
/**/{
/**/    /*<*/boot::$manager->override('iconv', 'libiconv', array('$from', '$to', '$s'))/*>*/;
/**/}

/**/if (extension_loaded('iconv'))
/**/{
/**/    if ('UTF-8//IGNORE' !== iconv_get_encoding('input_encoding'))
            iconv_set_encoding('input_encoding'   , 'UTF-8//IGNORE') + ini_set('iconv.input_encoding',    'UTF-8//IGNORE');

/**/    if ('UTF-8//IGNORE' !== iconv_get_encoding('internal_encoding'))
            iconv_set_encoding('internal_encoding', 'UTF-8//IGNORE') + ini_set('iconv.internal_encoding', 'UTF-8//IGNORE');

/**/    if ('UTF-8//IGNORE' !== iconv_get_encoding('output_encoding'))
            iconv_set_encoding('output_encoding'  , 'UTF-8//IGNORE') + ini_set('iconv.output_encoding',   'UTF-8//IGNORE');
/**/}
/**/else
/**/{
        define('ICONV_IMPL', 'Patchwork');
        define('ICONV_VERSION', '1.0');
        define('ICONV_MIME_DECODE_STRICT', 1);
        define('ICONV_MIME_DECODE_CONTINUE_ON_ERROR', 2);

/**/    /*<*/boot::$manager->override('iconv',                     ':Iconv:', array('$from', '$to', '$s'))/*>*/;
/**/    /*<*/boot::$manager->override('iconv_get_encoding',        ':Iconv:', array('$type' => 'all'))/*>*/;
/**/    /*<*/boot::$manager->override('iconv_set_encoding',        ':Iconv:', array('$type', '$charset'))/*>*/;
/**/    /*<*/boot::$manager->override('iconv_mime_encode',         ':Iconv:', array('$name', '$value', '$pref' => INF))/*>*/;
/**/    /*<*/boot::$manager->override('ob_iconv_handler',          ':Iconv:', array('$buffer', '$mode'))/*>*/;
/**/    /*<*/boot::$manager->override('iconv_mime_decode_headers', ':Iconv:', array('$encoded_headers', '$mode' => 2, '$charset' => INF))/*>*/;
/**/
/**/    if (extension_loaded('mbstring'))
/**/    {
/**/        /*<*/boot::$manager->override('iconv_strlen',  'mb_strlen',  array('$s', '$enc' => INF))/*>*/;
/**/        /*<*/boot::$manager->override('iconv_strpos',  'mb_strpos',  array('$s', '$needle', '$offset' => 0, '$enc' => INF))/*>*/;
/**/        /*<*/boot::$manager->override('iconv_strrpos', 'mb_strrpos', array('$s', '$needle',                 '$enc' => INF))/*>*/;
/**/        /*<*/boot::$manager->override('iconv_substr',  'mb_substr',  array('$s', '$start', '$length' => PHP_INT_MAX, '$enc' => INF))/*>*/;
/**/        /*<*/boot::$manager->override('iconv_mime_decode', 'mb_decode_mimeheader', array('$encoded_headers', '$mode' => 2, '$charset' => INF))/*>*/;
/**/    }
/**/    else
/**/    {
/**/        extension_loaded('xml')
/**/            ? /*<*/boot::$manager->override('iconv_strlen', ':Iconv::strlen1', array('$s', '$enc' => INF))/*>*/
/**/            : /*<*/boot::$manager->override('iconv_strlen', ':Iconv::strlen2', array('$s', '$enc' => INF))/*>*/;
/**/
/**/        /*<*/boot::$manager->override('iconv_strpos',  ':Mbstring52:', array('$s', '$needle', '$offset' => 0, '$enc' => INF))/*>*/;
/**/        /*<*/boot::$manager->override('iconv_strrpos', ':Mbstring52:', array('$s', '$needle',                 '$enc' => INF))/*>*/;
/**/        /*<*/boot::$manager->override('iconv_substr',  ':Mbstring52:', array('$s', '$start', '$length' => PHP_INT_MAX, '$enc' => INF))/*>*/;
/**/        /*<*/boot::$manager->override('iconv_mime_decode',  ':Iconv:', array('$encoded_headers', '$mode' => 2, '$charset' => INF))/*>*/;
/**/    }
/**/}


// EXIF configuration

/**/if (extension_loaded('exif'))
/**/{
/**/    if ('UTF-8' !== strtoupper(ini_get('exif.encode_unicode')) && ini_get('exif.encode_unicode'))
            ini_set('exif.encode_unicode', 'UTF-8');

/**/    if ('UTF-8' !== strtoupper(ini_get('exif.encode_jis')) && ini_get('exif.encode_jis'))
            ini_set('exif.encode_jis', 'UTF-8');
/**/}


// utf8_encode/decode support enhanced to Windows-1252

/**/ /*<*/boot::$manager->override('utf8_encode', ':Utf8:', array('$s'))/*>*/;
/**/ /*<*/boot::$manager->override('utf8_decode', ':Utf8:', array('$s'))/*>*/;


// Check PCRE

/**/if (!preg_match('/^.$/u', '§')) throw boot::$manager->error('PCRE is not compiled with UTF-8 support');


// intl configuration

/**/if (!extension_loaded('intl'))
/**/{
/**/    /*<*/boot::$manager->override('normalizer_is_normalized', 'Normalizer::isNormalized', array('$s', '$form' => 'NFC'))/*>*/;
/**/    /*<*/boot::$manager->override('normalizer_normalize',     'Normalizer::normalize',    array('$s', '$form' => 'NFC'))/*>*/;

        define('GRAPHEME_EXTR_COUNT',    0);
        define('GRAPHEME_EXTR_MAXBYTES', 1);
        define('GRAPHEME_EXTR_MAXCHARS', 2);

/**/    /*<*/boot::$manager->override('grapheme_extract',  ':Intl:', array('$s', '$size', '$type' => 0, '$start' => 0, '&$next' => 0))/*>*/;
/**/    /*<*/boot::$manager->override('grapheme_stripos',  ':Intl:', array('$s', '$needle', '$offset' => 0))/*>*/;
/**/    /*<*/boot::$manager->override('grapheme_stristr',  ':Intl:', array('$s', '$needle', '$before_needle' => false))/*>*/;
/**/    /*<*/boot::$manager->override('grapheme_strlen',   ':Intl:', array('$s'))/*>*/;
/**/    /*<*/boot::$manager->override('grapheme_strpos',   ':Intl:', array('$s', '$needle', '$offset' => 0))/*>*/;
/**/    /*<*/boot::$manager->override('grapheme_strripos', ':Intl:', array('$s', '$needle', '$offset' => 0))/*>*/;
/**/    /*<*/boot::$manager->override('grapheme_strrpos',  ':Intl:', array('$s', '$needle', '$offset' => 0))/*>*/;
/**/    /*<*/boot::$manager->override('grapheme_strstr',   ':Intl:', array('$s', '$needle', '$before_needle' => false))/*>*/;
/**/    /*<*/boot::$manager->override('grapheme_substr',   ':Intl:', array('$s', '$start', '$len' => INF))/*>*/;
/**/}

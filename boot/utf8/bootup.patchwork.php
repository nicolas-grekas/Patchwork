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

use Patchwork\PHP\Override as o;

// basename() and pathinfo() are locale sensitive, but this breaks UTF-8 paths

/**/if ('' === basename('§'))
/**/{
        Patchwork\FunctionOverride(basename, o\Fs, $path, $suffix = '');
        Patchwork\FunctionOverride(pathinfo, o\Fs, $path, $option = INF);
/**/}


// Changing default charset to UTF-8, adding new $double_encode parameter (since 5.2.3)

     Patchwork\FunctionOverride(html_entity_decode, html_entity_decode, $s, $style = ENT_COMPAT, $charset = 'UTF-8');

/**/if (PHP_VERSION_ID < 50203)
/**/{
        Patchwork\FunctionOverride(htmlspecialchars, o\Utf8, $s, $style = ENT_COMPAT, $charset = 'UTF-8', $double_enc = true);
        Patchwork\FunctionOverride(htmlentities,     o\Utf8, $s, $style = ENT_COMPAT, $charset = 'UTF-8', $double_enc = true);
/**/}
/**/else
/**/{
/**/    // No override for htmlspecialchars() because ISO-8859-1 and UTF-8 are both compatible with ASCII, where the HTML_SPECIALCHARS table lies
        Patchwork\FunctionOverride(htmlentities, htmlentities, $s, $style = ENT_COMPAT, $charset = 'UTF-8', $double_enc = true);
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
            Patchwork\FunctionOverride(mail, o\Mbstring8bit, $to, $subject, $message, $headers = '', $params = '');
/**/    }
/**/
/**/    if (MB_OVERLOAD_STRING & (int) ini_get('mbstring.func_overload'))
/**/    {
            Patchwork\FunctionOverride(strlen,   o\Mbstring8bit, $s);
            Patchwork\FunctionOverride(strpos,   o\Mbstring8bit, $s, $needle, $offset = 0);
            Patchwork\FunctionOverride(strrpos,  o\Mbstring8bit, $s, $needle, $offset = 0);
            Patchwork\FunctionOverride(substr,   o\Mbstring8bit, $s, $start, $length = INF);
            Patchwork\FunctionOverride(stripos,  o\Mbstring8bit, $s, $needle, $offset = 0,   $enc = INF);
            Patchwork\FunctionOverride(stristr,  o\Mbstring8bit, $s, $needle, $part = false, $enc = INF);
            Patchwork\FunctionOverride(strrchr,  o\Mbstring8bit, $s, $needle, $part = false, $enc = INF);
            Patchwork\FunctionOverride(strripos, o\Mbstring8bit, $s, $needle, $offset = 0,   $enc = INF);
            Patchwork\FunctionOverride(strstr,   o\Mbstring8bit, $s, $needle, $part = false, $enc = INF);
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

        Patchwork\FunctionOverride(mb_convert_encoding,     o\Mbstring500, $s, $to, $from = INF);
        Patchwork\FunctionOverride(mb_decode_mimeheader,    o\Mbstring500, $s);
        Patchwork\FunctionOverride(mb_encode_mimeheader,    o\Mbstring500, $s, $charset = INF, $transfer_enc = INF, $lf = INF, $indent = INF);
        Patchwork\FunctionOverride(mb_convert_case,         o\Mbstring500, $s, $mode, $enc = INF);
        Patchwork\FunctionOverride(mb_internal_encoding,    o\Mbstring500, $enc = INF);
        Patchwork\FunctionOverride(mb_list_encodings,       o\Mbstring500);
        Patchwork\FunctionOverride(mb_parse_str,            parse_str,     $s, &$result = array());
        Patchwork\FunctionOverride(mb_strlen,               o\Mbstring500, $s, $enc = INF);
        Patchwork\FunctionOverride(mb_strpos,               o\Mbstring500, $s, $needle, $offset = 0, $enc = INF);
        Patchwork\FunctionOverride(mb_strtolower,           o\Mbstring500, $s, $enc = INF);
        Patchwork\FunctionOverride(mb_strtoupper,           o\Mbstring500, $s, $enc = INF);
        Patchwork\FunctionOverride(mb_substitute_character, o\Mbstring500, $char = INF);
        Patchwork\FunctionOverride(mb_substr_count,         substr_count,  $s, $needle);
        Patchwork\FunctionOverride(mb_substr,               o\Mbstring500, $s, $start, $length = PHP_INT_MAX, $enc = INF);
        Patchwork\FunctionOverride(mb_stripos,              o\Mbstring520, $s, $needle, $offset = 0,   $enc = INF);
        Patchwork\FunctionOverride(mb_stristr,              o\Mbstring520, $s, $needle, $part = false, $enc = INF);
        Patchwork\FunctionOverride(mb_strrchr,              o\Mbstring520, $s, $needle, $part = false, $enc = INF);
        Patchwork\FunctionOverride(mb_strrichr,             o\Mbstring520, $s, $needle, $part = false, $enc = INF);
        Patchwork\FunctionOverride(mb_strripos,             o\Mbstring520, $s, $needle, $offset = 0,   $enc = INF);
        Patchwork\FunctionOverride(mb_strrpos,              o\Mbstring520, $s, $needle, $offset = 0,   $enc = INF);
        Patchwork\FunctionOverride(mb_strstr,               o\Mbstring520, $s, $needle, $part = false, $enc = INF);

/**/    if (extension_loaded('mbstring'))
            Patchwork\FunctionOverride(mb_strrpos500, mb_strrpos,   $s, $needle, $enc = INF);
/**/    else
            Patchwork\FunctionOverride(mb_strrpos500, o\Mbstring500, $s, $needle, $enc = INF);
/**/}


// iconv configuration

/**/ // See http://php.net/manual/en/function.iconv.php#47428
/**/if (!function_exists('iconv') && function_exists('libiconv'))
/**/{
        Patchwork\FunctionOverride(iconv, libiconv, $from, $to, $s);
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

        Patchwork\FunctionOverride(iconv,                     o\Iconv, $from, $to, $s);
        Patchwork\FunctionOverride(iconv_get_encoding,        o\Iconv, $type = 'all');
        Patchwork\FunctionOverride(iconv_set_encoding,        o\Iconv, $type, $charset);
        Patchwork\FunctionOverride(iconv_mime_encode,         o\Iconv, $name, $value, $pref = INF);
        Patchwork\FunctionOverride(ob_iconv_handler,          o\Iconv, $buffer, $mode);
        Patchwork\FunctionOverride(iconv_mime_decode_headers, o\Iconv, $encoded_headers, $mode = 2, $charset = INF);
/**/
/**/    if (extension_loaded('mbstring'))
/**/    {
            Patchwork\FunctionOverride(iconv_strlen,  mb_strlen,  $s, $enc = INF);
            Patchwork\FunctionOverride(iconv_strpos,  mb_strpos,  $s, $needle, $offset = 0, $enc = INF);
            Patchwork\FunctionOverride(iconv_strrpos, mb_strrpos, $s, $needle,              $enc = INF);
            Patchwork\FunctionOverride(iconv_substr,  mb_substr,  $s, $start, $length = PHP_INT_MAX, $enc = INF);
            Patchwork\FunctionOverride(iconv_mime_decode, mb_decode_mimeheader, $encoded_headers, $mode = 2, $charset = INF);
/**/    }
/**/    else
/**/    {
/**/        if (extension_loaded('xml'))
                Patchwork\FunctionOverride(iconv_strlen, o\Iconv::strlen1, $s, $enc = INF);
/**/        else
                Patchwork\FunctionOverride(iconv_strlen, o\Iconv::strlen2, $s, $enc = INF);

            Patchwork\FunctionOverride(iconv_strpos,  o\Mbstring520, $s, $needle, $offset = 0, $enc => INF);
            Patchwork\FunctionOverride(iconv_strrpos, o\Mbstring520, $s, $needle,              $enc => INF);
            Patchwork\FunctionOverride(iconv_substr,  o\Mbstring520, $s, $start, $length = PHP_INT_MAX, $enc = INF);
            Patchwork\FunctionOverride(iconv_mime_decode, o\Iconv, $encoded_headers, $mode = 2, $charset = INF);
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

Patchwork\FunctionOverride(utf8_encode, o\Utf8, $s);
Patchwork\FunctionOverride(utf8_decode, o\Utf8, $s);


// Check PCRE

/**/if (!preg_match('/^.$/u', '§')) throw boot::$manager->error('PCRE is not compiled with UTF-8 support');


// intl configuration

/**/if (!extension_loaded('intl'))
/**/{
        Patchwork\FunctionOverride(normalizer_is_normalized, Normalizer::isNormalized, $s, $form = 'NFC');
        Patchwork\FunctionOverride(normalizer_normalize,     Normalizer::normalize,    $s, $form = 'NFC');

        define('GRAPHEME_EXTR_COUNT',    0);
        define('GRAPHEME_EXTR_MAXBYTES', 1);
        define('GRAPHEME_EXTR_MAXCHARS', 2);

        Patchwork\FunctionOverride(grapheme_extract,  o\Intl, $s, $size, $type = 0, $start = 0, &$next = 0);
        Patchwork\FunctionOverride(grapheme_stripos,  o\Intl, $s, $needle, $offset = 0);
        Patchwork\FunctionOverride(grapheme_stristr,  o\Intl, $s, $needle, $before_needle = false);
        Patchwork\FunctionOverride(grapheme_strlen,   o\Intl, $s);
        Patchwork\FunctionOverride(grapheme_strpos,   o\Intl, $s, $needle, $offset = 0);
        Patchwork\FunctionOverride(grapheme_strripos, o\Intl, $s, $needle, $offset = 0);
        Patchwork\FunctionOverride(grapheme_strrpos,  o\Intl, $s, $needle, $offset = 0);
        Patchwork\FunctionOverride(grapheme_strstr,   o\Intl, $s, $needle, $before_needle = false);
        Patchwork\FunctionOverride(grapheme_substr,   o\Intl, $s, $start, $len = INF);
/**/}

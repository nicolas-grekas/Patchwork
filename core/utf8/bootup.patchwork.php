<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

use Patchwork\PHP\Override as o;

// utf8_encode/decode support enhanced to Windows-1252

Patchwork\FunctionOverride(utf8_encode, o\Xml::cp1252_to_utf8, $s);
Patchwork\FunctionOverride(utf8_decode, o\Xml::utf8_to_cp1252, $s);


// basename() and pathinfo() are locale sensitive, but this breaks UTF-8 paths

/**/if ('' === basename('§'))
/**/{
        Patchwork\FunctionOverride(basename, o\Fs, $path, $suffix = '');
        Patchwork\FunctionOverride(pathinfo, o\Fs, $path, $option = -1);
/**/}


// mbstring configuration

/**/if (extension_loaded('mbstring'))
/**/{
/**/    if (ini_get_bool('mbstring.encoding_translation') && !in_array(strtolower(ini_get('mbstring.http_input')), array('pass', '8bit', 'utf-8')))
/**/        throw boot::$manager->error('Please disable "mbstring.encoding_translation" or set "mbstring.http_input" to "utf-8" or "pass"');

        mb_regex_encoding('UTF-8');
        ini_set('mbstring.script_encoding', 'pass');

/**/    if ('utf-8' !== strtolower(mb_internal_encoding()))
            mb_internal_encoding('UTF-8') + ini_set('mbstring.internal_encoding', 'UTF-8');

/**/    if ('none'  !== strtolower(mb_substitute_character()))
            mb_substitute_character('none') + ini_set('mbstring.substitute_character', 'none');

/**/    if (!in_array(strtolower(mb_http_output()), array('pass', '8bit')))
            mb_http_output('pass') + ini_set('mbstring.http_output', 'pass');

/**/    if (!in_array(strtolower(mb_language()), array('uni', 'neutral')))
            mb_language('uni') + ini_set('mbstring.language', 'uni');
/**/}
/**/else
/**/{
        const MB_OVERLOAD_MAIL = 1;
        const MB_OVERLOAD_STRING = 2;
        const MB_OVERLOAD_REGEX = 4;
        const MB_CASE_UPPER = 0;
        const MB_CASE_LOWER = 1;
        const MB_CASE_TITLE = 2;

        Patchwork\FunctionOverride(mb_convert_encoding,     o\Mbstring, $s, $to, $from = INF);
        Patchwork\FunctionOverride(mb_decode_mimeheader,    o\Mbstring, $s);
        Patchwork\FunctionOverride(mb_encode_mimeheader,    o\Mbstring, $s, $charset = INF, $transfer_enc = INF, $lf = INF, $indent = INF);
        Patchwork\FunctionOverride(mb_convert_case,         o\Mbstring, $s, $mode, $enc = INF);
        Patchwork\FunctionOverride(mb_internal_encoding,    o\Mbstring, $enc = INF);
        Patchwork\FunctionOverride(mb_list_encodings,       o\Mbstring);
        Patchwork\FunctionOverride(mb_parse_str,            parse_str,     $s, &$result = array());
        Patchwork\FunctionOverride(mb_strlen,               o\Mbstring, $s, $enc = INF);
        Patchwork\FunctionOverride(mb_strpos,               o\Mbstring, $s, $needle, $offset = 0, $enc = INF);
        Patchwork\FunctionOverride(mb_strtolower,           o\Mbstring, $s, $enc = INF);
        Patchwork\FunctionOverride(mb_strtoupper,           o\Mbstring, $s, $enc = INF);
        Patchwork\FunctionOverride(mb_substitute_character, o\Mbstring, $char = INF);
        Patchwork\FunctionOverride(mb_substr_count,         substr_count,  $s, $needle);
        Patchwork\FunctionOverride(mb_substr,               o\Mbstring, $s, $start, $length = 2147483647, $enc = INF);
        Patchwork\FunctionOverride(mb_stripos,              o\Mbstring, $s, $needle, $offset = 0,   $enc = INF);
        Patchwork\FunctionOverride(mb_stristr,              o\Mbstring, $s, $needle, $part = false, $enc = INF);
        Patchwork\FunctionOverride(mb_strrchr,              o\Mbstring, $s, $needle, $part = false, $enc = INF);
        Patchwork\FunctionOverride(mb_strrichr,             o\Mbstring, $s, $needle, $part = false, $enc = INF);
        Patchwork\FunctionOverride(mb_strripos,             o\Mbstring, $s, $needle, $offset = 0,   $enc = INF);
        Patchwork\FunctionOverride(mb_strrpos,              o\Mbstring, $s, $needle, $offset = 0,   $enc = INF);
        Patchwork\FunctionOverride(mb_strstr,               o\Mbstring, $s, $needle, $part = false, $enc = INF);
/**/}


// iconv configuration

/**/ // See http://php.net/manual/en/function.iconv.php#47428
/**/if (!function_exists('iconv') && function_exists('libiconv'))
/**/{
        function iconv($from, $to, $s) {return libiconv($from, $to, $s);};
/**/}

/**/if (extension_loaded('iconv'))
/**/{
/**/    if ('UTF-8' !== iconv_get_encoding('input_encoding'))
            iconv_set_encoding('input_encoding'   , 'UTF-8') + ini_set('iconv.input_encoding',    'UTF-8');

/**/    if ('UTF-8' !== iconv_get_encoding('internal_encoding'))
            iconv_set_encoding('internal_encoding', 'UTF-8') + ini_set('iconv.internal_encoding', 'UTF-8');

/**/    if ('UTF-8' !== iconv_get_encoding('output_encoding'))
            iconv_set_encoding('output_encoding'  , 'UTF-8') + ini_set('iconv.output_encoding',   'UTF-8');

/**/    if (PHP_VERSION_ID < 50400)
/**/    {
            Patchwork\FunctionOverride(iconv, o\Iconv::iconv_workaround52211, $from, $to, $s);
/**/    }
/**/}
/**/else
/**/{
        const ICONV_IMPL = 'Patchwork';
        const ICONV_VERSION = '1.0';
        const ICONV_MIME_DECODE_STRICT = 1;
        const ICONV_MIME_DECODE_CONTINUE_ON_ERROR = 2;

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
            Patchwork\FunctionOverride(iconv_substr,  mb_substr,  $s, $start, $length = 2147483647, $enc = INF);
            Patchwork\FunctionOverride(iconv_mime_decode, mb_decode_mimeheader, $encoded_headers, $mode = 2, $charset = INF);
/**/    }
/**/    else
/**/    {
/**/        if (extension_loaded('xml'))
                Patchwork\FunctionOverride(iconv_strlen, o\Iconv::strlen1, $s, $enc = INF);
/**/        else
                Patchwork\FunctionOverride(iconv_strlen, o\Iconv::strlen2, $s, $enc = INF);

            Patchwork\FunctionOverride(iconv_strpos,  o\Mbstring::mb_strpos,  $s, $needle, $offset = 0, $enc = INF);
            Patchwork\FunctionOverride(iconv_strrpos, o\Mbstring::mb_strrpos, $s, $needle,              $enc = INF);
            Patchwork\FunctionOverride(iconv_substr,  o\Mbstring::mb_substr,  $s, $start, $length = 2147483647, $enc = INF);
            Patchwork\FunctionOverride(iconv_mime_decode, o\Iconv, $encoded_headers, $mode = 2, $charset = INF);
/**/    }
/**/}


// EXIF configuration

/**/if (extension_loaded('exif'))
/**/{
/**/    if (ini_get('exif.encode_unicode') && 'UTF-8' !== strtoupper(ini_get('exif.encode_unicode')))
            ini_set('exif.encode_unicode', 'UTF-8');

/**/    if (ini_get('exif.encode_jis') && 'UTF-8' !== strtoupper(ini_get('exif.encode_jis')))
            ini_set('exif.encode_jis', 'UTF-8');
/**/}


// Check PCRE

/**/if (!preg_match('/^.$/u', '§')) throw boot::$manager->error('PCRE is not compiled with UTF-8 support');


// intl configuration

/**/if (!extension_loaded('intl'))
/**/{
        Patchwork\FunctionOverride(normalizer_is_normalized, o\Normalizer::isNormalized, $s, $form = o\Normalizer::NFC);
        Patchwork\FunctionOverride(normalizer_normalize,     o\Normalizer::normalize,    $s, $form = o\Normalizer::NFC);

        const GRAPHEME_EXTR_COUNT = 0;
        const GRAPHEME_EXTR_MAXBYTES = 1;
        const GRAPHEME_EXTR_MAXCHARS = 2;

        Patchwork\FunctionOverride(grapheme_extract,  o\Intl, $s, $size, $type = 0, $start = 0, &$next = 0);
        Patchwork\FunctionOverride(grapheme_stripos,  o\Intl, $s, $needle, $offset = 0);
        Patchwork\FunctionOverride(grapheme_stristr,  o\Intl, $s, $needle, $before_needle = false);
        Patchwork\FunctionOverride(grapheme_strlen,   o\Intl, $s);
        Patchwork\FunctionOverride(grapheme_strpos,   o\Intl, $s, $needle, $offset = 0);
        Patchwork\FunctionOverride(grapheme_strripos, o\Intl, $s, $needle, $offset = 0);
        Patchwork\FunctionOverride(grapheme_strrpos,  o\Intl, $s, $needle, $offset = 0);
        Patchwork\FunctionOverride(grapheme_strstr,   o\Intl, $s, $needle, $before_needle = false);
        Patchwork\FunctionOverride(grapheme_substr,   o\Intl, $s, $start, $len = 2147483647);
/**/}
/**/else
/**/{
/**/    if ('à' === grapheme_substr('éà', 1, -2)) // Test https://bugs.php.net/62759
/**/    {
            Patchwork\FunctionOverride(grapheme_substr, o\Intl::grapheme_substr_workaround62759, $s, $start, $len = 2147483647);
/**/    }

/**/    if (1 !== grapheme_stripos('ße', 'e')) // Test https://bugs.php.net/61860
/**/    {
            Patchwork\FunctionOverride(grapheme_stripos,  \Patchwork\Utf8::stripos,  $s, $needle, $offset = 0);
            Patchwork\FunctionOverride(grapheme_strripos, \Patchwork\Utf8::strripos, $s, $needle, $offset = 0);
            Patchwork\FunctionOverride(grapheme_stristr,  \Patchwork\Utf8::stristr,  $s, $needle, $before_needle = false);
/**/    }
/**/}

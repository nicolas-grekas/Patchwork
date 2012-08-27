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

/**/if (PCRE_VERSION < '8.32')
/**/{
        // (CRLF|([ZWNJ-ZWJ]|T+|L*(LV?V+|LV|LVT)T*|L+|[^Control])[Extend]*|[Control])
        // This regular expression is not up to date with the latest unicode grapheme cluster definition.
        // However, until http://bugs.exim.org/show_bug.cgi?id=1279 is fixed, it's still better than \X

        define('GRAPHEME_CLUSTER_RX', '(?:\r\n|(?:[ -~\x{200C}\x{200D}]|[ᆨ-ᇹ]+|[ᄀ-ᅟ]*(?:[가개갸걔거게겨계고과괘괴교구궈궤귀규그긔기까깨꺄꺠꺼께껴꼐꼬꽈꽤꾀꾜꾸꿔꿰뀌뀨끄끠끼나내냐냬너네녀녜노놔놰뇌뇨누눠눼뉘뉴느늬니다대댜댸더데뎌뎨도돠돼되됴두둬뒈뒤듀드듸디따때땨떄떠떼뗘뗴또똬뙈뙤뚀뚜뚸뛔뛰뜌뜨띄띠라래랴럐러레려례로롸뢔뢰료루뤄뤠뤼류르릐리마매먀먜머메며몌모뫄뫠뫼묘무뭐뭬뮈뮤므믜미바배뱌뱨버베벼볘보봐봬뵈뵤부붜붸뷔뷰브븨비빠빼뺘뺴뻐뻬뼈뼤뽀뽜뽸뾔뾰뿌뿨쀄쀠쀼쁘쁴삐사새샤섀서세셔셰소솨쇄쇠쇼수숴쉐쉬슈스싀시싸쌔쌰썌써쎄쎠쎼쏘쏴쐐쐬쑈쑤쒀쒜쒸쓔쓰씌씨아애야얘어에여예오와왜외요우워웨위유으의이자재쟈쟤저제져졔조좌좨죄죠주줘줴쥐쥬즈즤지짜째쨔쨰쩌쩨쪄쪠쪼쫘쫴쬐쬬쭈쭤쮀쮜쮸쯔쯰찌차채챠챼처체쳐쳬초촤쵀최쵸추춰췌취츄츠츼치카캐캬컈커케켜켸코콰쾌쾨쿄쿠쿼퀘퀴큐크킈키타태탸턔터테텨톄토톼퇘퇴툐투퉈퉤튀튜트틔티파패퍄퍠퍼페펴폐포퐈퐤푀표푸풔풰퓌퓨프픠피하해햐햬허헤혀혜호화홰회효후훠훼휘휴흐희히]?[ᅠ-ᆢ]+|[가-힣])[ᆨ-ᇹ]*|[ᄀ-ᅟ]+|[^\p{Cc}\p{Cf}\p{Zl}\p{Zp}])[\p{Mn}\p{Me}\x{09BE}\x{09D7}\x{0B3E}\x{0B57}\x{0BBE}\x{0BD7}\x{0CC2}\x{0CD5}\x{0CD6}\x{0D3E}\x{0D57}\x{0DCF}\x{0DDF}\x{200C}\x{200D}\x{1D165}\x{1D16E}-\x{1D172}]*|[\p{Cc}\p{Cf}\p{Zl}\p{Zp}])');
/**/}
/**/else
/**/{
        define('GRAPHEME_CLUSTER_RX', '\X');
/**/}

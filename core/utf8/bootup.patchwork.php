<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

use Patchwork\PHP\Shim as s;

// utf8_encode/decode support enhanced to Windows-1252

Patchwork\FunctionShim(utf8_encode, s\Xml::cp1252_to_utf8, $s);
Patchwork\FunctionShim(utf8_decode, s\Xml::utf8_to_cp1252, $s);


// basename() and pathinfo() are locale sensitive, but this breaks UTF-8 paths

/**/if ('' === basename('§'))
/**/{
        Patchwork\FunctionShim(basename, s\Fs, $path, $suffix = '');
        Patchwork\FunctionShim(pathinfo, s\Fs, $path, $option = -1);
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

        Patchwork\FunctionShim(mb_convert_encoding,     s\Mbstring, $s, $to, $from = INF);
        Patchwork\FunctionShim(mb_decode_mimeheader,    s\Mbstring, $s);
        Patchwork\FunctionShim(mb_encode_mimeheader,    s\Mbstring, $s, $charset = INF, $transfer_enc = INF, $lf = INF, $indent = INF);
        Patchwork\FunctionShim(mb_convert_case,         s\Mbstring, $s, $mode, $enc = INF);
        Patchwork\FunctionShim(mb_internal_encoding,    s\Mbstring, $enc = INF);
        Patchwork\FunctionShim(mb_list_encodings,       s\Mbstring);
        Patchwork\FunctionShim(mb_parse_str,            parse_str,     $s, &$result = array());
        Patchwork\FunctionShim(mb_strlen,               s\Mbstring, $s, $enc = INF);
        Patchwork\FunctionShim(mb_strpos,               s\Mbstring, $s, $needle, $offset = 0, $enc = INF);
        Patchwork\FunctionShim(mb_strtolower,           s\Mbstring, $s, $enc = INF);
        Patchwork\FunctionShim(mb_strtoupper,           s\Mbstring, $s, $enc = INF);
        Patchwork\FunctionShim(mb_substitute_character, s\Mbstring, $char = INF);
        Patchwork\FunctionShim(mb_substr_count,         substr_count,  $s, $needle);
        Patchwork\FunctionShim(mb_substr,               s\Mbstring, $s, $start, $length = 2147483647, $enc = INF);
        Patchwork\FunctionShim(mb_stripos,              s\Mbstring, $s, $needle, $offset = 0,   $enc = INF);
        Patchwork\FunctionShim(mb_stristr,              s\Mbstring, $s, $needle, $part = false, $enc = INF);
        Patchwork\FunctionShim(mb_strrchr,              s\Mbstring, $s, $needle, $part = false, $enc = INF);
        Patchwork\FunctionShim(mb_strrichr,             s\Mbstring, $s, $needle, $part = false, $enc = INF);
        Patchwork\FunctionShim(mb_strripos,             s\Mbstring, $s, $needle, $offset = 0,   $enc = INF);
        Patchwork\FunctionShim(mb_strrpos,              s\Mbstring, $s, $needle, $offset = 0,   $enc = INF);
        Patchwork\FunctionShim(mb_strstr,               s\Mbstring, $s, $needle, $part = false, $enc = INF);
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
            Patchwork\FunctionShim(iconv, s\Iconv::iconv_workaround52211, $from, $to, $s);
/**/    }
/**/}
/**/else
/**/{
        const ICONV_IMPL = 'Patchwork';
        const ICONV_VERSION = '1.0';
        const ICONV_MIME_DECODE_STRICT = 1;
        const ICONV_MIME_DECODE_CONTINUE_ON_ERROR = 2;

        Patchwork\FunctionShim(iconv,                     s\Iconv, $from, $to, $s);
        Patchwork\FunctionShim(iconv_get_encoding,        s\Iconv, $type = 'all');
        Patchwork\FunctionShim(iconv_set_encoding,        s\Iconv, $type, $charset);
        Patchwork\FunctionShim(iconv_mime_encode,         s\Iconv, $name, $value, $pref = INF);
        Patchwork\FunctionShim(ob_iconv_handler,          s\Iconv, $buffer, $mode);
        Patchwork\FunctionShim(iconv_mime_decode_headers, s\Iconv, $encoded_headers, $mode = 2, $charset = INF);
/**/
/**/    if (extension_loaded('mbstring'))
/**/    {
            Patchwork\FunctionShim(iconv_strlen,  mb_strlen,  $s, $enc = INF);
            Patchwork\FunctionShim(iconv_strpos,  mb_strpos,  $s, $needle, $offset = 0, $enc = INF);
            Patchwork\FunctionShim(iconv_strrpos, mb_strrpos, $s, $needle,              $enc = INF);
            Patchwork\FunctionShim(iconv_substr,  mb_substr,  $s, $start, $length = 2147483647, $enc = INF);
            Patchwork\FunctionShim(iconv_mime_decode, mb_decode_mimeheader, $encoded_headers, $mode = 2, $charset = INF);
/**/    }
/**/    else
/**/    {
/**/        if (extension_loaded('xml'))
                Patchwork\FunctionShim(iconv_strlen, s\Iconv::strlen1, $s, $enc = INF);
/**/        else
                Patchwork\FunctionShim(iconv_strlen, s\Iconv::strlen2, $s, $enc = INF);

            Patchwork\FunctionShim(iconv_strpos,  s\Mbstring::mb_strpos,  $s, $needle, $offset = 0, $enc = INF);
            Patchwork\FunctionShim(iconv_strrpos, s\Mbstring::mb_strrpos, $s, $needle,              $enc = INF);
            Patchwork\FunctionShim(iconv_substr,  s\Mbstring::mb_substr,  $s, $start, $length = 2147483647, $enc = INF);
            Patchwork\FunctionShim(iconv_mime_decode, s\Iconv, $encoded_headers, $mode = 2, $charset = INF);
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
        Patchwork\FunctionShim(normalizer_is_normalized, s\Normalizer::isNormalized, $s, $form = s\Normalizer::NFC);
        Patchwork\FunctionShim(normalizer_normalize,     s\Normalizer::normalize,    $s, $form = s\Normalizer::NFC);

        const GRAPHEME_EXTR_COUNT = 0;
        const GRAPHEME_EXTR_MAXBYTES = 1;
        const GRAPHEME_EXTR_MAXCHARS = 2;

        Patchwork\FunctionShim(grapheme_extract,  s\Intl, $s, $size, $type = 0, $start = 0, &$next = 0);
        Patchwork\FunctionShim(grapheme_stripos,  s\Intl, $s, $needle, $offset = 0);
        Patchwork\FunctionShim(grapheme_stristr,  s\Intl, $s, $needle, $before_needle = false);
        Patchwork\FunctionShim(grapheme_strlen,   s\Intl, $s);
        Patchwork\FunctionShim(grapheme_strpos,   s\Intl, $s, $needle, $offset = 0);
        Patchwork\FunctionShim(grapheme_strripos, s\Intl, $s, $needle, $offset = 0);
        Patchwork\FunctionShim(grapheme_strrpos,  s\Intl, $s, $needle, $offset = 0);
        Patchwork\FunctionShim(grapheme_strstr,   s\Intl, $s, $needle, $before_needle = false);
        Patchwork\FunctionShim(grapheme_substr,   s\Intl, $s, $start, $len = 2147483647);
/**/}
/**/else
/**/{
/**/    if ('à' === grapheme_substr('éà', 1, -2)) // Test https://bugs.php.net/62759
/**/    {
            Patchwork\FunctionShim(grapheme_substr, s\Intl::grapheme_substr_workaround62759, $s, $start, $len = 2147483647);
/**/    }

/**/    if (1 !== grapheme_stripos('ße', 'e')) // Test https://bugs.php.net/61860
/**/    {
            Patchwork\FunctionShim(grapheme_stripos,  \Patchwork\Utf8::stripos,  $s, $needle, $offset = 0);
            Patchwork\FunctionShim(grapheme_strripos, \Patchwork\Utf8::strripos, $s, $needle, $offset = 0);
            Patchwork\FunctionShim(grapheme_stristr,  \Patchwork\Utf8::stristr,  $s, $needle, $before_needle = false);
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

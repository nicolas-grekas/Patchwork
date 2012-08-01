<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class converter_txt_html extends converter_abstract
{
    protected

    $cols = 78;


    protected static

    $charMap = array(
        array('┼','├','┬','┌','┤','│','┐','┴','└','─','┘','┼','┠','┯','┏','┨','┃','┓','┷','┗','━','┛','•','□','☆','○','■','★','◎','●','△','●','○','□','●','≪ ↑ ↓ ' ),
        array('+','|','-','+','|','|','+','-','+','-','+','+','|','-','+','|','|','+','-','+','-','+','*','+','o','#','@','-','=','x','%','*','o','#','#','<=UpDn ')
    ),
    $textAnchor = array();


    function __construct($cols = false)
    {
        $cols && $this->cols = (int) $cols;
    }

    function convertData($html)
    {
        // Style according to the Netiquette
        $html = preg_replace('#<(?:b|strong)\b[^>]*>(\s*)#iu' , '$1*', $html);
        $html = preg_replace('#(\s*)</(?:b|strong)\b[^>]*>#iu', '*$1', $html);
        $html = preg_replace('#<u\b[^>]*>(\s*)#iu' , '$1_', $html);
        $html = preg_replace('#(\s*)</u\b[^>]*>#iu', '_$1', $html);

        // Remove <sub> and <sup> tags
        $html = preg_replace('#<(/?)su[bp]\b([^>]*)>#iu' , '<$1span$2>', $html);

        // Fill empty alt attributes with whitespace, clear src attributes
        $html = preg_replace('#(<[^>]+\balt=")"#iu', '$1 "', $html);
        $html = preg_replace('#(<[^>]+\bsrc=")(?:[^"]*)"#iu', '$1"', $html);

        // Inline URLs
        $html = preg_replace_callback(
            '#<a\b[^>]*\shref="([^"]*)"[^>]*>(.*?)</a\b[^>]*>#isu',
            array(__CLASS__, 'buildTextAnchor'),
            $html
        );

        // Convert html-entities to UTF-8 for w3m
        $html = str_replace(
            array('&quot;',     '&lt;',     '&gt;',     '&#039;',     '"',      '<',    '>',    "'"),
            array('&amp;quot;', '&amp;lt;', '&amp;gt;', '&amp;#039;', '&quot;', '&lt;', '&gt;', '&#039;'),
            FILTER::get($html, 'text')
        );
        $html = html_entity_decode($html, ENT_COMPAT, 'UTF-8');

        $file = tempnam(PATCHWORK_ZCACHE, 'converter');

        Patchwork::writeFile($file, $html);

        $html = escapeshellarg($file);
        $html = `w3m -dump -cols {$this->cols} -T text/html -I UTF-8 -O UTF-8 {$html}`;
        $html = str_replace(self::$charMap[0], self::$charMap[1], $html);

        $html = strtr($html, self::$textAnchor);
        self::$textAnchor = array();

        unlink($file);

        return $html;
    }

    function convertFile($file)
    {
        $html = file_get_contents($file);

        return $this->convertData($html);
    }

    protected static function buildTextAnchor($m)
    {
        $a = $m[2];
        $m = trim($m[1]);
        $m = preg_replace('"^mailto:\s*"i', '', $m);

        $b = false !== strpos($m, '&') ? html_entity_decode($m, ENT_COMPAT, 'UTF-8') : $m;
        $b = preg_replace_callback('"[^-a-zA-Z0-9_.~,/?:@&=+$#%]+"', array(__CLASS__, 'rawurlencodeCallback'), $b);
        $len = strlen($b);

        $c = '';
        do $c .= md5(mt_rand());
        while (strlen($c) < $len);
        $c = substr($c, 0, $len);

        self::$textAnchor[$c] = $b;

        if ('' === trim($a)) {}
        else if (false === stripos($a, $m)) $a .= " &lt;{$c}&gt;";
        else $a = str_ireplace($m, $c, " {$a} ");

        return $a;
    }

    protected static function rawurlencodeCallback($m)
    {
        return rawurlencode($m[0]);
    }
}

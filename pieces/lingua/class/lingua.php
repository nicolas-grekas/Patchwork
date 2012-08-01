<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class lingua
{
    protected static

    $sort_keys,
    $sort_length;


    /**
     * Sorts an array of array/objects according to a list of keys/properties.
     */
    static function sort(&$array, $keys, $associative = false)
    {
        self::$sort_keys = preg_split("'\s*,\s*'u", $keys);
        self::$sort_length = count(self::$sort_keys);

        $associative
            ? uasort($array, array('self', 'sort_compare'))
            : usort($array, array('self', 'sort_compare'));
    }

    protected static function sort_compare($a, $b)
    {
        $a = (array) $a;
        $b = (array) $b;

        for ($i = 0; $i < self::$sort_length; ++$i)
        {
            $key = self::$sort_keys[$i];
            if (' desc' == strtolower(substr($key, -5)))
            {
                $key = substr($key, 0, -5);
                $cmp = -1;
            }
            else $cmp = 1;

            if ($a[$key] < $b[$key]) return -$cmp;
            else if ($a[$key] > $b[$key]) return $cmp;
        }

        return 0;
    }

    protected static $ACCENT = array(
        'AÀÁÂÃÄÅĀĂĄǺ','aàáâãäåāăąǻ','CĆĈÇĊČ','cćĉçċč','DĐĎ','dđď',
        'EÈÉÊËĒĔĘĖĚ','eèéêëēĕęėě','GĜĢĞĠ','gĝģğġ','HĤĦ','hĥħ',
        'IÌÍÎĨÏĪĬĮİ','iìíîĩïīĭįı','JĴ','jĵ','KĶ','kķ','LĹĻŁĿĽ','lĺļłŀľ',
        'NŃÑŅŇ','nńñņň','OÒÓŐÔÕÖØŌŎǾ','oòóőôõöøōŏǿ','RŔŖŘ','rŕŗř',
        'SŚŜŞŠ','sśŝşš','TŢŦŤ','tţŧť','UÙÚŰÛŨÜŮŪŬŲ','uùúűûũüůūŭų',
        'WẀẂŴẄ','wẁẃŵẅ','YỲÝŶŸ','yỳýŷÿ','ZŹŻŽ','zźżž'
    );
    protected static $ACCENT_FROM = array();
    protected static $ACCENT_TO = array();
    protected static $ACCENT_LENGTH;

    protected static function initAccents()
    {
        self::$ACCENT_FROM = array(
            "/[\p{Mn}\p{Lm}]/u", '/[ÆǼǢ]/u', '/[æǽǣ]/u', '/ß/u', '/Œ/u', '/œ/u', '/[ʤʣʥ]/u', '/ﬀ/u',
            '/ﬃ/u', '/ﬄ/u', '/ﬁ/u', '/ﬂ/u', '/ƕ/u', '/Ƣ/u', '/ƣ/u', '/[ﬆﬅ]/u', '/ʨ/u', '/ʦ/u', '/ƻ/u'
        );
        self::$ACCENT_TO   = array(
            ''                 , 'AE'      , 'ae'      , 'ss'  , 'OE'  , 'oe'  , 'dz'      , 'ff'  ,
            'ffi' , 'ffl' , 'fi'  , 'fl'  , 'hv'  , 'OI'  , 'oi'  , 'st'     , 'tc'  , 'ts'  , '2'
        );

        $len = self::$ACCENT_LENGTH = count(self::$ACCENT);
        for ($i = 0; $i < $len; ++$i)
        {
            $v = self::$ACCENT[$i];
            self::$ACCENT_FROM[] = '/[' . substr($v, 1) . ']/u';
            self::$ACCENT_TO[]   = $v[0];
        }
    }

    /**
     * Removes all accents from an UTF-8 string, and optionnaly change it's case.
     */
    static function stripAccents($str, $case = 0)
    {
        if (!self::$ACCENT_FROM) self::initAccents();

        $str = preg_replace(self::$ACCENT_FROM, self::$ACCENT_TO, $str);

        return $case>0 ? mb_strtoupper($str) : $case<0 ? mb_strtolower($str) : $str;
    }

    /**
     * Transform a string to a RegExp that is not sentive to accents and punctuation
     */
    static function getRxQuoteInsensitive($str, $delimiter = '')
    {
        if (!self::$ACCENT_FROM) self::initAccents();

        $str = '' === $delimiter ? preg_quote($str) : preg_quote($str, $delimiter);

        $str = preg_replace('/["«»“”″]/u', '["«»“”″]', $str);
        $str = preg_replace("/['‘’′ʿ]/u" , "['‘’′ʿ]" , $str);
        $str = preg_replace("/[- _]/u"   , '[- _]'   , $str);
        $str = preg_replace(self::$ACCENT_FROM, self::$ACCENT_TO, $str);

        $len = self::$ACCENT_LENGTH;
        for ($i = 0; $i < $len; ++$i)
        {
            $v = self::$ACCENT[$i];
            $str = str_replace($v[0], "[{$v}]", $str);
        }

        return $str;
    }

    /**
     * Return an alphabetic RexExp class, with accents support
     */
    static function getAlphaRx($number = false)
    {
        return '\pL\p{Mn}' . ($number ? '\pN' : '');
    }

    /**
     * Clean an string to make it suitable for a search
     */
    static function getKeywords($kw)
    {
        $a = '[^' . self::getAlphaRx(true) . ']';
        $b = '[\p{Lt}\p{Lu}]';

        $kw = ' ' . $kw . ' ';

        // Acronym
        $kw = preg_replace_callback("'{$a}({$b}(?:\.{$b})+){$a}'u", array(__CLASS__, 'acronym_callback'), $kw);

        // Ponctuation
        $kw = preg_replace("'{$a}+'u", ' ', $kw);

        // Accents and case
        $kw = self::stripAccents($kw, -1);

        return trim($kw);
    }

    static function acronym_callback($m)
    {
        return str_replace('.', '', " {$m[1]} ");
    }
}

<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class agent_QEngine extends agent
{
    protected

    $EXCLUDED_KEYWORD = '',
    $minKwLen = 2,

    $ACCENT_FROM = array('À','Á','Â','Ã','Ä','Å','Ā','Ă','Ą','Ǻ','à','á','â','ã','ä','å','ā','ă','ą','ǻ','Ć','Ĉ','Ç','Ċ','Č','ć','ĉ','ç','ċ','č','Đ','Ď','đ','ď','È','É','Ê','Ë','Ē','Ĕ','Ę','Ė','Ě','è','é','ê','ë','ē','ĕ','ę','ė','ě','Ĝ','Ģ','Ğ','Ġ','ĝ','ģ','ğ','ġ','Ĥ','Ħ','ĥ','ħ','Ì','Í','Î','Ĩ','Ï','Ī','Ĭ','Į','İ','ì','í','î','ĩ','ï','ī','ĭ','į','ı','Ĵ','ĵ','Ķ','ķ','Ĺ','Ļ','Ł','Ŀ','Ľ','ĺ','ļ','ł','ŀ','ľ','Ń','Ñ','Ņ','Ň','ń','ñ','ņ','ň','Ò','Ó','Ő','Ô','Õ','Ö','Ø','Ō','Ŏ','Ǿ','ò','ó','ő','ô','õ','ö','ø','ō','ŏ','ǿ','Ŕ','Ŗ','Ř','ŕ','ŗ','ř','Ś','Ŝ','Ş','Š','ś','ŝ','ş','š','Ţ','Ŧ','Ť','ţ','ŧ','ť','Ù','Ú','Ű','Û','Ũ','Ü','Ů','Ū','Ŭ','Ų','ù','ú','ű','û','ũ','ü','ů','ū','ŭ','ų','Ẁ','Ẃ','Ŵ','Ẅ','ẁ','ẃ','ŵ','ẅ','Ỳ','Ý','Ŷ','Ÿ','ỳ','ý','ŷ','ÿ','Ź','Ż','Ž','ź','ż','ž','Æ','Ǽ','æ','ǽ','œ','Œ','ß'),
    $ACCENT_TO = array('A','A','A','A','A','A','A','A','A','A','a','a','a','a','a','a','a','a','a','a','C','C','C','C','C','c','c','c','c','c','D','D','d','d','E','E','E','E','E','E','E','E','E','e','e','e','e','e','e','e','e','e','G','G','G','G','g','g','g','g','H','H','h','h','I','I','I','I','I','I','I','I','I','i','i','i','i','i','i','i','i','i','J','j','K','k','L','L','L','L','L','l','l','l','l','l','N','N','N','N','n','n','n','n','O','O','O','O','O','O','O','O','O','O','o','o','o','o','o','o','o','o','o','o','R','R','R','r','r','r','S','S','S','S','s','s','s','s','T','T','T','t','t','t','U','U','U','U','U','U','U','U','U','U','u','u','u','u','u','u','u','u','u','u','W','W','W','W','w','w','w','w','Y','Y','Y','Y','y','y','y','y','Z','Z','Z','z','z','z','AE','AE','ae','ae','oe','OE','ss');


    function getKwIndex($keyword)
    {
        $K = array();

        foreach ($keyword as $i => &$k)
        {
            foreach ($this->getKeywords($k) as $k)
            {
                if (isset($K[$k])) $K[$k] .= ",$i";
                else $K[$k] = $i;
            }
        }

        ksort($K);

        return '{' . $this->getPrefixTree($K) . '}';
    }

    function getKeywords($kw)
    {
        $kw = str_replace($this->ACCENT_FROM, $this->ACCENT_TO, $kw);
        $kw = strtoupper($kw);
        $kw = preg_replace("'[^A-Z0-9]+'u", ' ', $kw);
        $kw = preg_replace("' ((" . $this->EXCLUDED_KEYWORD . ") )+'u", ' ', ' '.$kw.' ');
        $kw = explode(' ', $kw);

        foreach ($kw as $i => &$v) if (strlen($v)<$this->minKwLen) unset($kw[$i]);

        return $kw;
    }


    // Original version of this function: http://cvs.php.net/viewvc.cgi/phpdoc/scripts/quickref/prefixcompress.php

    protected function getPrefixTree(&$kw)
    {
        $result = '';
        $KLen = count($kw);

        while ($KLen)
        {
            reset($kw);
            list($K0, $I0) = each($kw);
            $K0Len = strlen($K0);

            if ($result !== '') $result .= ',';

            if ($KLen == 1) return $result . $this->S($K0, $I0);

            list($K1, $I1) = each($kw);

            if ($KLen == 2)
            {
                if (strpos($K1, $K0) === 0) $result .= $this->P($K0, $I0) . $this->S(substr($K1, $K0Len), $I1) . '}';
                else if (0 !== strncmp($K0, $K1, 2)) $result .= $this->S($K0, $I0) . ',' . $this->S($K1, $I1);
                else
                {
                    $len = 2;
                    while ($len < $K0Len && 0 === strncmp($K0, $K1, $len+1)) ++$len;

                    $result .= $this->P(substr($K0, 0, $len)) . $this->S(substr($K0, $len), $I0) . ',' . $this->S(substr($K1, $len), $I1) . '}';
                }

                return $result;
            }

            list($K2, $I2) = each($kw);

            if (@($K0[0] != $K1[0] || $K0[1] != $K1[1] && $K2[0] != $K0[0]))
            {
                array_shift($kw);
                --$KLen;

                $result .= $this->S($K0, $I0);
                continue;
            }

            $bestsave = 0;
            $bestremain = $kw;
            $bestresult = $this->S($K0, $I0);
            array_shift($bestremain);

            if (strpos($K1, $K0) === 0)
            {
                $subs = array();

                while ($bestremain && strpos(key($bestremain), $K0) === 0) $subs[ substr(key($bestremain), $K0Len) ] = array_shift($bestremain);

                $bestresult = $this->P($K0, $I0) . $this->getPrefixTree($subs) . '}';
                $bestsave = count($subs)*$K0Len - 1;

                if (count($bestremain)) ++$bestsave;
            }

            while (--$K0Len)
            {
                $K1 = substr($K0, 0, $K0Len);
                $count = 0;

                reset($kw);
                while ((list($K0) = each($kw)) && strpos($K0, $K1) === 0) ++$count;

                $save = ($count-1)*$K0Len - 2;

                if ($count < $KLen) ++$save;

                if ($save > $bestsave)
                {
                    $bestsave = $save;
                    $bestremain = $kw;
                    $subs = array();

                    if ($count) do $subs[ substr(key($bestremain), $K0Len) ] = array_shift($bestremain);
                    while (--$count);

                    $bestresult = $this->P($K1) . $this->getPrefixTree($subs) . '}';
                }
            }

            $result .= $bestresult;
            $kw = $bestremain;
            $KLen = count($kw);
        }

        return $result;
    }

    protected function P($k, $v = false)
    {
        $a = (is_numeric($k[0]) ? "\"$k\"" : $k) . ':{';
        if ($v !== false) $a .= '$:' . (is_int($v) ? $v : "\"$v\"") . ',';
        return $a;
    }

    protected function S($k, $v)
    {
        return (is_numeric($k[0]) ? "\"$k\"" : $k) . ':' . (is_int($v) ? $v : "\"$v\"");
    }
}

<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pipe_stripAccents
{
    static function php($str, $case = 0)
    {
        return lingua::stripAccents($str, $case);
    }

    static function js()
    {
        $p = 'P$stripAccents';

        ?>/*<script>*/

(function()
{
    var $ACCENT_RX = [],
        $ACCENT_LIGFROM = [/[\uCCA8\uCCB1]/g,/[ÆǼǢ]/g,/[æǽǣ]/g,/ß/g,/Œ/g,/œ/g,/ʤʣʥ/g,/ﬀ/g,/ﬃ/g ,/ﬄ/g ,/ﬁ/g,/ﬂ/g,/ƕ/g,/Ƣ/g,/ƣ/g,/ﬆﬅ/g,/ʨ/g,/ʦ/g,/ƻ/g],
        $ACCENT_LIGTO   = [''               ,'AE'    ,'ae'    ,'ss','OE','oe','dz'  ,'ff','ffi','ffl','fi','fl','hv','OI','oi','st' ,'tc','ts','2' ],
        $ACCENT = navigator.userAgent.indexOf('Safari')<0 /* Without this test, the next while line makes Safari crash (at least version <= 2.0)*/
            ? ['AÀÁÂÃÄÅĀĂĄǺ','aàáâãäåāăąǻ','CĆĈÇĊČ','cćĉçċč','DĐĎ','dđď','EÈÉÊËĒĔĘĖĚ','eèéêëēĕęėě','GĜĢĞĠ','gĝģğġ','HĤĦ','hĥħ','IÌÍÎĨÏĪĬĮİ','iìíîĩïīĭįı','JĴ','jĵ','KĶ','kķ','LĹĻŁĿĽ','lĺļłŀľ','NŃÑŅŇ','nńñņň','OÒÓŐÔÕÖØŌŎǾ','oòóőôõöøōŏǿ','RŔŖŘ','rŕŗř','SŚŜŞŠ','sśŝşš','TŢŦŤ','tţŧť','UÙÚŰÛŨÜŮŪŬŲ','uùúűûũüůūŭų','WẀẂŴẄ','wẁẃŵẅ','YỲÝŶŸ','yỳýŷÿ','ZŹŻŽ','zźżž']
            : ['AÀÁÂÃÄ','aàáâãä','CÇ','cç','EÈÉÊË','eèéêë','IÌÍÎĨÏ','iìíîĩï','NÑ','nñ','OÒÓŐÔÕÖ','oòóőôõö','UÙÚÛŨÜ','uùúûũü','YỲÝŶŸ','yỳýŷÿ'],
        $i = $ACCENT.length;

    while ($i--) $ACCENT_RX[$i] = new RegExp('['+$ACCENT[$i]+']', 'g');

    return function($str, $case)
    {
        var $i = $ACCENT.length;

        while ($i--) $str = $str.replace($ACCENT_RX[$i], $ACCENT[$i].charAt(0));

        for ($i in $ACCENT_LIGFROM) $str = $str.replace($ACCENT_LIGFROM[$i], $ACCENT_LIGTO[$i]);

        return $case>0 ? $str.toUpperCase() : $case<0 ? $str.toLowerCase() : $str;
    }
})()

<?php   }
}

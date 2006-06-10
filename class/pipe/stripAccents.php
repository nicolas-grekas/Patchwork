<?php

class pipe_stripAccents
{
	static function php($str, $case = 0)
	{
		return LIB::stripAccents($str, $case);
	}

	static function js()
	{
		$p = 'P$' . substr(__CLASS__, 5);

		?>/*<script>*/

<?php echo $p?> = function($str, $case)
{
	var $ACCENT = <?php echo $p?>.$ACCENT,
		$ACCENT_RX = <?php echo $p?>.$ACCENT_RX,
		$ACCENT_LIGFROM = [/[\uCCA8\uCCB1]/g,/[ÆǼǢ]/g,/[æǽǣ]/g,/ß/g,/Œ/g,/œ/g,/ʤʣʥ/g,/ﬀ/g,/ﬃ/g ,/ﬄ/g ,/ﬁ/g,/ﬂ/g,/ƕ/g,/Ƣ/g,/ƣ/g,/ﬆﬅ/g,/ʨ/g,/ʦ/g,/ƻ/g],
		$ACCENT_LIGTO   = [''               ,'AE'    ,'ae'    ,'ss','OE','oe','dz'  ,'ff','ffi','ffl','fi','fl','hv','OI','oi','st' ,'tc','ts','2' ],
		$i = $ACCENT.length;

	while ($i--) $str = $str.replace(ACCENT_RX[$i], ACCENT[$i].charAt(0));

	for ($i in $ACCENT_LIGFROM) $str = $str.replace($ACCENT_LIGFROM[$i], $ACCENT_LIGTO[$i]);

	return $case>0 ? $str.toUpperCase() : $case<0 ? $str.toLowerCase() : $str;
}

<?php echo $p?>.$ACCENT = navigator.userAgent.indexOf('Safari')<0 /* Without this test, the next while line makes Safari crash (at least version <= 2.0)*/
	? ['AÀÁÂÃÄÅĀĂĄǺ','aàáâãäåāăąǻ','CĆĈÇĊČ','cćĉçċč','DĐĎ','dđď','EÈÉÊËĒĔĘĖĚ','eèéêëēĕęėě','GĜĢĞĠ','gĝģğġ','HĤĦ','hĥħ','IÌÍÎĨÏĪĬĮİ','iìíîĩïīĭįı','JĴ','jĵ','KĶ','kķ','LĹĻŁĿĽ','lĺļłŀľ','NŃÑŅŇ','nńñņň','OÒÓŐÔÕÖØŌŎǾ','oòóőôõöøōŏǿ','RŔŖŘ','rŕŗř','SŚŜŞŠ','sśŝşš','TŢŦŤ','tţŧť','UÙÚŰÛŨÜŮŪŬŲ','uùúűûũüůūŭų','WẀẂŴẄ','wẁẃŵẅ','YỲÝŶŸ','yỳýŷÿ','ZŹŻŽ','zźżž']
	: ['AÀÁÂÃÄ','aàáâãä','CÇ','cç','EÈÉÊË','eèéêë','IÌÍÎĨÏ','iìíîĩï','NÑ','nñ','OÒÓŐÔÕÖ','oòóőôõö','UÙÚÛŨÜ','uùúûũü','YỲÝŶŸ','yỳýŷÿ'];

<?php echo $p?>.$ACCENT_RX = [];
$i = <?php echo $p?>.$ACCENT.length;
while ($i--) <?php echo $p?>.$ACCENT_RX[$i] = new RegExp('['+<?php echo $p?>.$ACCENT[$i]+']', 'g');

<?php 	}
}

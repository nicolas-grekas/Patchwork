ACCENT = navigator.userAgent.indexOf('Safari')<0 /* Without this test, the next while line makes Safari crash (at least version <= 2.0)*/
	? ['AÀÁÂÃÄÅĀĂĄǺ','aàáâãäåāăąǻ','CĆĈÇĊČ','cćĉçċč','DĐĎ','dđď','EÈÉÊËĒĔĘĖĚ','eèéêëēĕęėě','GĜĢĞĠ','gĝģğġ','HĤĦ','hĥħ','IÌÍÎĨÏĪĬĮİ','iìíîĩïīĭįı','JĴ','jĵ','KĶ','kķ','LĹĻŁĿĽ','lĺļłŀľ','NŃÑŅŇ','nńñņň','OÒÓŐÔÕÖØŌŎǾ','oòóőôõöøōŏǿ','RŔŖŘ','rŕŗř','SŚŜŞŠ','sśŝşš','TŢŦŤ','tţŧť','UÙÚŰÛŨÜŮŪŬŲ','uùúűûũüůūŭų','WẀẂŴẄ','wẁẃŵẅ','YỲÝŶŸ','yỳýŷÿ','ZŹŻŽ','zźżž']
	: ['AÀÁÂÃÄ','aàáâãä','CÇ','cç','EÈÉÊË','eèéêë','IÌÍÎĨÏ','iìíîĩï','NÑ','nñ','OÒÓŐÔÕÖ','oòóőôõö','UÙÚÛŨÜ','uùúûũü','YỲÝŶŸ','yỳýŷÿ'];

ACCENT_LIGFROM = [/[\uCCA8\uCCB1]/g,/[ÆǼǢ]/g,/[æǽǣ]/g,/ß/g,/Œ/g,/œ/g,/ʤʣʥ/g,/ﬀ/g,/ﬃ/g ,/ﬄ/g ,/ﬁ/g,/ﬂ/g,/ƕ/g,/Ƣ/g,/ƣ/g,/ﬆﬅ/g,/ʨ/g,/ʦ/g,/ƻ/g];
ACCENT_LIGTO   = [''               ,'AE'    ,'ae'    ,'ss','OE','oe','dz'  ,'ff','ffi','ffl','fi','fl','hv','OI','oi','st' ,'tc','ts','2' ];

ACCENT_RX = [];
$i = ACCENT.length;
while ($i--) ACCENT_RX[$i] = new RegExp('['+ACCENT[$i]+']', 'g');

ACCENT_ALPHANUM = '0-9a-zA-Z\xcc\xa8\xcc\xb1ÆǼǢæǽǣßŒœʤʣʥﬀﬃﬄﬁﬂƕƢƣﬆﬅʨʦƻ' + ACCENT.join('');

RegExp.quote = function($str, $accent)
{
	var $i = ACCENT.length - 1;

	$str = $str.replace(/([\.\\\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:])/g, '\\$1');

	if ($accent)
	{
		do $str = $str.replace(ACCENT_RX[$i], '['+ACCENT[$i]+']');
		while (--$i);
	}

	return $str;
}

function stripAccents($str, $case)
{
	var $i = ACCENT.length;

	while ($i--) $str = $str.replace(ACCENT_RX[$i], ACCENT[$i].charAt(0));

	for ($i in ACCENT_LIGFROM) $str = $str.replace(ACCENT_LIGFROM[$i], ACCENT_LIGTO[$i]);

	return $case>0 ? $str.toUpperCase() : $case<0 ? $str.toLowerCase() : $str;
}

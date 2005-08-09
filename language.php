<?php

header('Expires: ' . gmdate('D, d M Y H:i:s', time() + CIA_MAXAGE) . ' GMT');
header('Cache-Control: max-age=' . CIA_MAXAGE .',public');
header('Vary: Accept-Language');

function HTTP_Best_Language($supported)
{
	$candidates = array();

	foreach (explode(',', @$_SERVER['HTTP_ACCEPT_LANGUAGE']) as $item)
	{
		$item = explode(';q=', $item);
		if ($item[0] = trim($item[0])) $candidates[ $item[0] ] = isset($item[1]) ? (double) trim($item[1]) : 1;
	}

	$lang = $supported[0];
	$qMax = 0;

	foreach ($candidates as $l => $q) if (
		$q > $qMax
		&& (
			in_array($l, $supported)
			|| (
				($tiret = strpos($l, '-'))
				&& in_array($l = substr($l, 0, $tiret), $supported)
			)
		)
	)
	{
		$qMax = $q;
		$lang = $l;
	}

	return $lang;
}

$lang = HTTP_Best_Language(explode('|', CIA_LANG_LIST));

?><html><head><script><!--
document.cookie='JS=1; path=/'
document.cookie='JS=1; expires=Sun, 17-Jan-2038 19:14:07 GMT; path=/'
top.location='<?php echo $lang?>/'
//--></script><meta http-equiv="refresh" content="0; URL=<?php echo $lang?>/" /></head><body>Choose a language :<ul><?php

foreach (explode('|', CIA_LANG_LIST) as $l)
{
	echo $l == $lang
		? "<li><a href='$l/'><b>$l</b></a></li>\n"
		: "<li><a href='$l/'>$l</a></li>\n";
}

?></ul></body></html><?php

exit;

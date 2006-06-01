<?php

header('Content-Type: text/html; charset=UTF-8');
header('Expires: ' . gmdate('D, d M Y H:i:s', CIA_TIME + CIA_MAXAGE) . ' GMT');
header('Cache-Control: max-age=' . CIA_MAXAGE .',public');
header('Vary: Accept-Language', false);
setcookie('JS', '0', 2147364847, '/');

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

$lang = explode('__', $_SERVER['CIA_HOME'], 2);
$lang = implode(HTTP_Best_Language(explode('|', $CONFIG['lang_list'])), $lang);
$lang = htmlspecialchars($lang);

?><html><head><title>...</title><script type="text/javascript">/*<![CDATA[*/
if(window.Error&&navigator.userAgent.indexOf('Safari')<0)document.cookie='JS=1; path=/',document.cookie='JS=1; expires=Sun, 17-Jan-2038 19:14:07 GMT; path=/'
location.replace("<?php echo $lang?>")
/*]]>*/</script><meta http-equiv="refresh" content="0; URL=<?php echo $lang?>" /></head><body><a href="<?php echo $lang?>"><?php echo $lang?></a></body></html><?php

exit;

<?php

$contentType = array(
	'.html' => 'text/html; charset=UTF-8',
	'.htm' => 'text/html; charset=UTF-8',
	'.css' => 'text/css; charset=UTF-8',
	'.js' => 'text/javascript; charset=UTF-8',

	'.png' => 'image/png',
	'.gif' => 'image/gif',
	'.jpg' => 'image/jpeg',
);

$contentType = $contentType[$path];

unset($source);

$i = 0;
$len = count($cia_paths);
$lang = CIA::__LANG__() . DIRECTORY_SEPARATOR;
$l_ng = '__' . DIRECTORY_SEPARATOR;

do
{
	$path = $cia_paths[$i++] . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR;

	switch (DEBUG)
	{
		case 5 : if (file_exists($path . $lang . $agent . ".5")) {$source = $path . $lang . $agent . ".5"; break;}
		case 4 : if (file_exists($path . $lang . $agent . ".4")) {$source = $path . $lang . $agent . ".4"; break;}
		case 3 : if (file_exists($path . $lang . $agent . ".3")) {$source = $path . $lang . $agent . ".3"; break;}
		case 2 : if (file_exists($path . $lang . $agent . ".2")) {$source = $path . $lang . $agent . ".2"; break;}
		case 1 : if (file_exists($path . $lang . $agent . ".1")) {$source = $path . $lang . $agent . ".1"; break;}
		default: if (file_exists($path . $lang . $agent       )) {$source = $path . $lang . $agent       ; break;}
	}

	if (!isset($source)) switch (DEBUG)
	{
		case 5 : if (file_exists($path . $l_ng . $agent . ".5")) {$source = $path . $l_ng . $agent . ".5"; break;}
		case 4 : if (file_exists($path . $l_ng . $agent . ".4")) {$source = $path . $l_ng . $agent . ".4"; break;}
		case 3 : if (file_exists($path . $l_ng . $agent . ".3")) {$source = $path . $l_ng . $agent . ".3"; break;}
		case 2 : if (file_exists($path . $l_ng . $agent . ".2")) {$source = $path . $l_ng . $agent . ".2"; break;}
		case 1 : if (file_exists($path . $l_ng . $agent . ".1")) {$source = $path . $l_ng . $agent . ".1"; break;}
		default: if (file_exists($path . $l_ng . $agent       )) {$source = $path . $l_ng . $agent       ; break;}
	}

	if (isset($source)) break;
}
while (--$len);

if (isset($source))
{
	CIA::header('Content-Type: ' . $contentType);
	CIA::setMaxage(-1);
	CIA::setExpires(true);
	CIA::writeWatchTable('public/static', 'tmp/');

	$i = stat($source);
	echo $i[1], '-', $i[7], '-', $i[9];
	ob_end_clean();

	header('Content-Length: ' . $i[7]);

	readfile($source);

	exit;
}

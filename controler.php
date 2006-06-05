<?php

$contentType = array(
	'.html' => 'text/html; charset=UTF-8',
	'.htm' => 'text/html; charset=UTF-8',
	'.css' => 'text/css; charset=UTF-8',
	'.js' => 'text/javascript; charset=UTF-8',

	'.png' => 'image/png',
	'.gif' => 'image/gif',
	'.jpg' => 'image/jpeg',
	'.jpeg' => 'image/jpeg',
);

$contentType = $contentType[$path];

$i = 0;
$len = count($cia_paths);
$lang = CIA::__LANG__() . DIRECTORY_SEPARATOR;
$l_ng = '__' . DIRECTORY_SEPARATOR;

$source = false;

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

	if ($source) break;


	switch (DEBUG)
	{
		case 5 : if (file_exists($path . $l_ng . $agent . ".5")) {$source = $path . $l_ng . $agent . ".5"; break;}
		case 4 : if (file_exists($path . $l_ng . $agent . ".4")) {$source = $path . $l_ng . $agent . ".4"; break;}
		case 3 : if (file_exists($path . $l_ng . $agent . ".3")) {$source = $path . $l_ng . $agent . ".3"; break;}
		case 2 : if (file_exists($path . $l_ng . $agent . ".2")) {$source = $path . $l_ng . $agent . ".2"; break;}
		case 1 : if (file_exists($path . $l_ng . $agent . ".1")) {$source = $path . $l_ng . $agent . ".1"; break;}
		default: if (file_exists($path . $l_ng . $agent       )) {$source = $path . $l_ng . $agent       ; break;}
	}

	if ($source) break;
}
while (--$len);

if ($source)
{
	CIA::header('Content-Type: ' . $contentType);
	CIA::setMaxage(-1);
	CIA::writeWatchTable('public/static', 'zcache/');

	readfile($source);

	exit;
}

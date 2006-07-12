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
$lang = CIA::__LANG__() . '/';
$l_ng = '__/';

$source = false;

do
{
	$path = $cia_paths[$i++] . '/public/';

	if (file_exists($source = $path . $lang . $agent)) break;
	if (file_exists($source = $path . $l_ng . $agent)) break;
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

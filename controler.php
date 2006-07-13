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
$len = count($GLOBALS['cia_paths']);
$lang = self::__LANG__() . '/';
$l_ng = '__/';

$source = false;

do
{
	$path = $GLOBALS['cia_paths'][$i++] . '/public/';

	if (file_exists($source = $path . $lang . $agent)) break;
	if (file_exists($source = $path . $l_ng . $agent)) break;
}
while (--$len);

if ($source)
{
	self::header('Content-Type: ' . $contentType);
	self::setMaxage(-1);
	self::writeWatchTable('public/static', 'zcache/');

	readfile($source);

	exit;
}

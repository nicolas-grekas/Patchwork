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

$path1 = explode(PATH_SEPARATOR, get_include_path());
$len = count($path1);

$lang = '/' . CIA_LANG . '/';

for ($i = 0; $i < $len; ++$i)
{
	switch (substr($path1[$i], -1))
	{
		case '':
		case '\\':
		case '/': break;
		default: $path1[$i] .= DIRECTORY_SEPARATOR;
	}

	$path1[$i] .= 'public';

	if (file_exists($path1[$i] . $lang . $agent))
	{
		$path1[$i] .= $lang . $agent;
		break;
	}
	else $path1[$i] .= "/__/{$agent}";
}

if ($i == $len)
{
	$lang = '/__/';
	for ($i = 0; $i < $len; ++$i) if (file_exists($path1[$i])) break;
}

if ($i < $len)
{
	CIA::header('Content-Type: ' . $contentType);
	CIA::setMaxage(-1);
	CIA::setExpires(true);
	readfile($path1[$i]);
}

exit;

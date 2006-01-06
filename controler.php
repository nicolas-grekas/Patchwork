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

$path = explode(PATH_SEPARATOR, get_include_path());
$len = count($path);

$lang = '/' . CIA_LANG . '/';

for ($i = 0; $i < $len; ++$i)
{
	switch (substr($path[$i], -1))
	{
		case '':
		case '\\':
		case '/': break;
		default: $path[$i] .= DIRECTORY_SEPARATOR;
	}

	$path[$i] .= 'public';

	if (file_exists($path[$i] . $lang . $agent))
	{
		$path[$i] .= $lang . $agent;
		break;
	}
	else $path[$i] .= "/__/{$agent}";
}

if ($i == $len)
{
	$lang = '/__/';
	for ($i = 0; $i < $len; ++$i) if (file_exists($path[$i])) break;
}

if ($i < $len)
{
	$path = $path[$i];

	CIA::header('Content-Type: ' . $contentType);
	CIA::setMaxage(-1);
	CIA::setExpires(true);
	CIA::writeWatchTable('public/static', 'tmp/');

	$i = stat($path);
	echo $i[1], '-', $i[7], '-', $i[9];
	ob_end_clean();
	
	readfile($path[$i]);
}

exit;

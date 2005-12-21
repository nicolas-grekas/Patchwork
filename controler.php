<?php

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
	$path1 = $path1[$i];
	$path2 = dirname($_SERVER['SCRIPT_FILENAME']) . $lang . $agent;

	$h1 = fopen($path1, 'rb');

	if ($h1)
	{
		$h2 = @fopen($path2, 'wb');
		if (!$h2)
		{
			CIA::makeDir($path2);
			$h2 = fopen($path2, 'wb');
		}

		if ($h2)
		{
			while (!feof($h1)) fwrite($h2, fread($h1, 8192), 8192);
			fclose($h2);
		}

		fclose($h1);
	}

	if ($h2) CIA::writeWatchTable('public/static', $path2);

	CIA::setMaxage(-1);
	CIA::setExpires(true);
	readfile($path1);
}

exit;

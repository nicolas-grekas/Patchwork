<?php

$tmp = md5(uniqid(mt_rand(), true));

$h = fopen($tmp, 'wb');

$source = file_get_contents($source);

if (DEBUG)
{
	$source = preg_replace("'^#>([^>].*)$'m", '$1', $source);
}
else
{
	$source = preg_replace("'^#>>>\s*^.*^#<<<\s*$'mse", 'preg_replace("/[^\r\n]+/", "", "$0")', $source);
}

fwrite($h, $source, strlen($source));

fclose($h);

if ('WIN' == substr(PHP_OS, 0, 3)) 
{
	$h = new COM('Scripting.FileSystemObject');
	$h->GetFile($paths[0] . '/' . $tmp)->Attributes |= 2;
	$h = @unlink($cache);
}

rename($tmp, $cache);

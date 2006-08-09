<?php

if ('WIN' == substr(PHP_OS, 0, 3))
{
	$h = @unlink($cache);
	copy($source, $cache);
	$h = new COM('Scripting.FileSystemObject');
	$h->GetFile($paths[0] . '/' . $cache)->Attributes |= 2;
}
else copy($source, $cache);

<?php // vim: set enc=utf-8 ai noet ts=4 sw=4 fdm=marker:

class extends driver_convertTo_abstract
{
	function file($file)
	{
		$file = escapeshellarg($file);
		return `antiword -t -w 0 {$file}`;
	}
}

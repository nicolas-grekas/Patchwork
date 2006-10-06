<?php

class extends driver_convertTo_abstract
{
	function file($file)
	{
		$file = escapeshellarg($file);
		return `antiword -t -w 0 {$file}`;
	}
}

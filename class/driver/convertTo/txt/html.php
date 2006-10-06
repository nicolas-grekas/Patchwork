<?php

class extends driver_convertTo_abstract
{
	function file($file)
	{
		$file = escapeshellarg($file);
		return `w3m -dump -cols 80 -T text/html -I UTF-8 -O UTF-8 {$file}`;
	}

}

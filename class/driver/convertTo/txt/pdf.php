<?php

class extends driver_convertTo_abstract
{
	function file($file)
	{
		$file = escapeshellarg($file);
		return `pdftotext -enc UTF-8 {$file} -`;
	}
}

<?php

class driver_convertTo_txt_pdf extends driver_convertTo_abstract
{
	static function file($file)
	{
		$file = escapeshellarg($file);
		$file = passthru("pdftotext -enc UTF-8 {$file} /dev/stdout", $status);

		return $status ? false : $file;
	}
}

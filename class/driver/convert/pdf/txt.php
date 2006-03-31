<?php

class driver_convert_pdf_txt extends driver_convert_abstract
{
	static function file($file)
	{
		$file = escapeshellarg($file);
		$file = passthru("pdftotext -enc UTF-8 {$file} /dev/stdout", $status);

		return $status ? false : $file;
	}
}

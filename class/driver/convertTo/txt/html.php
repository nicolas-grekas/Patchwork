<?php

class driver_convertTo_txt_html extends driver_convertTo_abstract
{
	static function file($file)
	{
		$file = escapeshellarg($file);
		$file = passthru("w3m -dump -cols 80 -T text/html -I UTF-8 -O UTF-8 {$file}", $status);

		return $status ? false : $file;
	}

}

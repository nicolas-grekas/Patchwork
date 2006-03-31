<?php

class driver_convert_html_txt extends
{
	static function file($file)
	{
		$file = escapeshellarg($file);
		$file = passthru("w3m -dump -cols 80 -T text/html -I UTF-8 -O UTF-8 {$file}", $status);

		return $status ? false : $file;
	}

}

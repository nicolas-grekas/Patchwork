<?php

class driver_convert_doc_txt extends driver_convert_abstract
{
	static function file($file)
	{
		$file = escapeshellarg($file);
		$file = passthru("antiword -t -w 0 {$file}", $status);

		return $status ? false : $file;
	}
}

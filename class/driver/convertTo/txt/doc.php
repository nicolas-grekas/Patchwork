<?php

class driver_convertTo_txt_doc extends driver_convertTo_abstract
{
	static function file($file)
	{
		$file = escapeshellarg($file);
		$file = passthru("antiword -t -w 0 {$file}", $status);

		return $status ? false : $file;
	}
}

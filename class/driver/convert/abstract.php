<?php

abstract class driver_convert_abstract
{
	abstract static function file($file);

	static function data($data)
	{
		$file = tempnam('./tmp', 'convert');

		file_put_contents($file, $data);

		$data = self::file($file);

		unlink($file);

		return $data;
	}
}

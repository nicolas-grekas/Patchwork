<?php

abstract class driver_convertTo_abstract
{
	abstract function file($file);

	function data($data)
	{
		$file = tempnam('./tmp', 'convert');

		file_put_contents($file, $data);

		$data = $this->file($file);

		unlink($file);

		return $data;
	}
}

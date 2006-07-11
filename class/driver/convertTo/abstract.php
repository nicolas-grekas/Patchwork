<?php

abstract class
{
	abstract function file($file);

	function data($data)
	{
		$file = tempnam('./tmp', 'convert');

		CIA::writeFile($file, $data);

		$data = $this->file($file);

		unlink($file);

		return $data;
	}
}

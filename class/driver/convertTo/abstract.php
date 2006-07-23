<?php // vim: set enc=utf-8 ai noet ts=4 sw=4 fdm=marker:

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

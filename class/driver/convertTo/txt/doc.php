<?php

class driver_convertTo_txt_doc extends driver_convertTo_abstract
{
	function file($file)
	{
		$file = escapeshellarg($file);
		return `antiword -t -w 0 {$file}`;
	}
}

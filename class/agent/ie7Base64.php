<?php

class extends agent_bin
{
	function compose($o)
	{
		if (false !== strpos(@$_SERVER['QUERY_STRING'], ';'))
		{
			$data = explode(';', $_SERVER['QUERY_STRING']);
			CIA::header('Content-Type: ' . $data[0]);

			$data = explode(',', $data[1]),
			echo base64_decode($data[1]);
		}

		return $o;
	}
}

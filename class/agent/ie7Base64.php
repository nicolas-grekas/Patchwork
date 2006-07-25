<?php

/*
 * Add the following in your HTML to enable Dean Edwards IE7:
 * <!--[if lt IE 7]><script src="{$home}js/ie7/ie7.js" type="text/javascript"></script ><![endif]-->
 */

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

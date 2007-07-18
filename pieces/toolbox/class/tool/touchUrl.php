<?php /*********************************************************************
 *
 *   Copyright : (C) 2006 Nicolas Grekas. All rights reserved.
 *   Email     : nicolas.grekas+patchwork@espci.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL, see COPYING
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


class
{
	/**
	 * Sends the request to the webserver but don't wait for the response.
	 */
	static function call($url)
	{
		$url = patchwork::base($url, true);

		if (!preg_match("'^(https?)://(.*?)((?::[0-9]+)?)(/.*)$'", $url, $h)) throw new Exception('Illegal URL');

		$url = $h[4];

		if ('https' == $h[1])
		{
			$port = '443';
			$req = 'ssl://';
		}
		else
		{
			$port = '80';
			$req = 'tcp://';
		}

		$host = $h[2];
		$req .= $host;

		if ($h = $h[3])
		{
			if (substr($h, 1) != $port) $host .= $h;
			$port = substr($h, 1);
		}

		$h = fsockopen($req, $port, $errno, $errstr, 5);

		if (!$h) throw new Exception("Socket error n°{$errno}: {$errstr}");

		socket_set_blocking($h, 0);

		$req  = "GET {$url} HTTP/1.0\r\n";
		$req .= "Host: {$host}\r\n";
		$req .= "Connection: Close\r\n\r\n";

		do
		{
			$len = fwrite($h, $req);
			$req = substr($req, $len);
		}
		while (false !== $len && false !== $req);

		fclose($h);
	}
}

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
		if (!preg_match("'^(https?)://(.*?)((?::[0-9]+)?)(/.*)$'i", $url, $h)) throw new Exception('Illegal URL');

		$url = $h[4];

		if ('https' == strtolower($h[1]))
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

		if ($h = $h[3])
		{
			if (substr($h, 1) != $port) $host .= $h;
			$port = substr($h, 1);
		}

		$req .= ':' . $port;

		$h = stream_socket_client($req, $errno, $errstr, 5);

		if (!$h) throw new Exception("Socket error n°{$errno}: {$errstr}");

		stream_set_blocking($h, 0);

		$req  = "GET {$url} HTTP/1.1\r\n";
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

<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class
{
	/**
	 * Sends the request to the webserver but don't wait for the response.
	 */
	static function touch($url)
	{
		$url = p::base($url, true);

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
		$req .= gethostbyname($host);

		if ($h = $h[3])
		{
			if (substr($h, 1) != $port) $host .= $h;
			$port = substr($h, 1);
		}

		$h = fsockopen($req, $port, $errno, $errstr, 5);

		if (!$h) W("Socket error n°{$errno}: {$errstr}");
		else
		{
			socket_set_blocking($h, 0);

			$req  = "GET {$url} HTTP/1.0\r\n";
			$req .= "Host: {$host}\r\n";
			$req .= "Connection: close\r\n\r\n";

			do
			{
				$len = fwrite($h, $req);
				$req = substr($req, $len);
			}
			while (false !== $len && false !== $req);

			fclose($h);
		}
	}
}

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


class extends CIA
{
	static $contentType = array(
		'.html' => 'text/html; charset=UTF-8',
		'.htm' => 'text/html; charset=UTF-8',
		'.css' => 'text/css; charset=UTF-8',
		'.js' => 'text/javascript; charset=UTF-8',
		'.htc' => 'text/x-component; charset=UTF-8',
		'.xml' => 'application/xml',

		'.png' => 'image/png',
		'.gif' => 'image/gif',
		'.jpg' => 'image/jpeg',
		'.jpeg' => 'image/jpeg',

		'.doc' => 'application/msword',
		'.pdf' => 'application/pdf',
	);

	protected static $filterRx;

	static function call($agent, $mime)
	{
		if ($agent = CIA::resolvePublicPath($agent))
		{
			$mime = strtolower($mime);
			$mime = isset(self::$contentType[$mime]) ? self::$contentType[$mime] : false;

			if (extension_loaded('fileinfo') && ($i = finfo_open(FILEINFO_SYMLINK|FILEINFO_MIME)))
			{
				$mime = finfo_file($i, $agent);
				finfo_close($i);
			}
			else if (function_exists('mime_content_type'))
			{
				$mime = mime_content_type($agent);
			}

			if (!$mime) $mime = 'application/octet-stream';

			CIA::setMaxage(-1);
			CIA::writeWatchTable('public/static', 'zcache/');

			CIA::readfile($agent, $mime);

			exit;
		}
	}

	static function readfile($file, $mime)
	{
		$size = filesize($file);

		CIA::header('Content-Type: ' . $mime);
		false !== stripos($mime, 'html') && header('P3P: CP="' . $GLOBALS['CONFIG']['P3P'] . '"');
		CIA::$binaryMode = true;
		CIA::$LastModified = filemtime($file);
		CIA::$ETag = $size .'-'. CIA::$LastModified .'-'. fileinode($file);
		CIA::disable();

		$gzip = CIA::gzipAllowed($mime);
		$gzip || ob_start();

		ob_start(array(__CLASS__, 'ob_filterOutput'), 8192);

		// Transform relative URLs to absolute ones
		if ('text/css' == substr($mime, 0, 8))
		{
			self::$filterRx = "@([\s:]url\(\s*[\"']?)(?![/\\\\#\"']|[^\)\n\r:/\"']+?:)@i";
			ob_start(array(__CLASS__, 'filter'), 8192);
		}
		else if ('text/html' == substr($mime, 0, 9) || 'text/x-component' === substr($mime, 0, 16))
		{
			self::$filterRx = "@(<[^<>]+?\s(?:href|src)\s*=\s*[\"']?)(?![/\\\\#\"']|[^\n\r:/\"']+?:)@i";
			ob_start(array(__CLASS__, 'filter'), 8192);
		}


		$h = fopen($file, 'rb');
		echo fread($h, 256); // For CIA::ob_filterOutput to fix IE

		if ($gzip)
		{
			if ('HEAD' == $_SERVER['REQUEST_METHOD']) ob_end_clean();
			$data = '';
		}
		else
		{
			ob_end_flush();
			$data = ob_get_clean();
			$size += strlen($data) - 256;
			header('Content-Length: ' . $size);
		}

		if ('HEAD' != $_SERVER['REQUEST_METHOD'])
		{
			echo $data;
			feof($h) || fpassthru($h);
		}

		fclose($h);
	}

	static function filter($buffer, $mode)
	{
		static $rest = '';

		$base = CIA::__BASE__() . dirname($_SERVER['CIA_REQUEST']) . '/';

		$buffer = preg_split(self::$filterRx, $rest . $buffer, -1, PREG_SPLIT_DELIM_CAPTURE);

		$len = count($buffer);
		for ($i = 1; $i < $len; $i += 2) $buffer[$i] .= $base;

		if (PHP_OUTPUT_HANDLER_END & $mode) $rest = '';
		else
		{
			$base = array_pop($buffer);
			$rest = substr($base, 4096);
			array_push($buffer, substr($base, 0, 4096));
		}

		return implode('', $buffer);
	}
}

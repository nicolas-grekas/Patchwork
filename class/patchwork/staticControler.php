<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 3 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


class extends patchwork
{
	// Extension to Content-Type map
	// This list doesn't have to exhaustive:
	// only types which can be handled by the browser
	// or one of its plugin need to be listed here.

	static $contentType = array(
		'.html' => 'text/html; charset=UTF-8',
		'.htm' => 'text/html; charset=UTF-8',
		'.css' => 'text/css; charset=UTF-8',
		'.js' => 'text/javascript; charset=UTF-8',
		'.htc' => 'text/x-component; charset=UTF-8',
		'.xml' => 'application/xml',
		'.swf' => 'application/x-shockwave-flash',

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
		if ($agent = p::resolvePublicPath($agent))
		{
			$mime = strtolower($mime);
			$mime = isset(self::$contentType[$mime]) ? self::$contentType[$mime] : 'application/octet-stream';

			p::setMaxage(-1);
			p::writeWatchTable('public/static', 'zcache/');

			p::readfile($agent, $mime);

			exit;
		}
	}

	static function readfile($file, $mime)
	{
		if (strlen($file) < 2 || !('/' == $file[0] || ':' == $file[1])) $file = resolvePath($file);
		$mime = strtolower($mime);

		$head = 'HEAD' == $_SERVER['REQUEST_METHOD'];
		$gzip = p::gzipAllowed($mime);
		$filter = $gzip || $head || !$CONFIG['xsendfile'] || in_array($mime, p::$ieSniffedTypes);

		header('Content-Type: ' . $mime);
		false !== strpos($mime, 'html') && header('P3P: CP="' . $CONFIG['P3P'] . '"');

		$size = filesize($file);
		p::$ETag = $size .'-'. p::$LastModified .'-'. fileinode($file);
		p::$LastModified = filemtime($file);
		p::$binaryMode = true;
		p::disable();

		class_exists('SESSION', false) && SESSION::close();
		DB(true);


		$gzip   || ob_start();
		$filter && ob_start(array(__CLASS__, 'ob_filterOutput'), 8192);


		// Transform relative URLs to absolute ones
		if ($gzip)
		{
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
		}


		if ($filter)
		{
			$h = fopen($file, 'rb');
			echo $starting_data = fread($h, 256); // For patchwork::ob_filterOutput to fix IE

			if ($gzip)
			{
				if ($head) ob_end_clean();
				$data = '';
				$starting_data = false;
			}
			else
			{
				ob_end_flush();
				$data = ob_get_clean();
				$size += strlen($data) - strlen($starting_data);
				$starting_data = $data == $starting_data;
			}
		}
		else $starting_data = true;


		if (!$head)
		{
			if ($starting_data && $CONFIG['xsendfile']) header('X-Sendfile: ' . $file);
			else
			{
				if ($range = $starting_data && !$gzip)
				{
					header('Accept-Ranges: bytes');

					$range = isset($_SERVER['HTTP_RANGE']) ? patchwork_httpRange::negociate($size, self::$ETag, self::$LastModified) : false;
				}
				else header('Accept-Ranges: none');

				set_time_limit(0);
				ignore_user_abort(false);

				if ($range) patchwork_httpRange::sendChunks($range, $h, $mime, $size);
				else
				{
					header('Content-Length: ' . $size);
					echo $data;
					feof($h) || fpassthru($h);
				}
			}
		}


		$filter && fclose($h);
	}

	static function filter($buffer, $mode)
	{
		static $rest = '', $base;

		isset($base) || $base = p::__BASE__() . dirname($_SERVER['PATCHWORK_REQUEST']) . '/';

		$buffer = preg_split(self::$filterRx, $rest . $buffer, -1, PREG_SPLIT_DELIM_CAPTURE);

		$len = count($buffer);
		for ($i = 1; $i < $len; $i += 2) $buffer[$i] .= $base;

		if (PHP_OUTPUT_HANDLER_END & $mode) $rest = '';
		else
		{
			--$len;
			$rest = substr($buffer[$len], 4096);
			$buffer[$len] = substr($buffer[$len], 0, 4096);
		}

		$buffer = implode('', $buffer);

		return $buffer;
	}
}

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


class extends patchwork
{
	// Map from extensions to content-types
	// This list doesn't have to be exhaustive:
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


	static function sendTemplate()
	{
		$template = array_shift($_GET);
		$template = str_replace('\\', '/', $template);
		$template = str_replace('../', '/', $template);

		echo 'w(0';

		$ctemplate = p::getContextualCachePath("templates/$template", 'txt');

		TURBO || p::syncTemplate($template, $ctemplate);

		$readHandle = true;

		if ($h = p::fopenX($ctemplate, $readHandle))
		{
			p::openMeta('agent__template/' . $template, false);
			$compiler = new ptlCompiler_js(false);
			echo $template = ',[' . $compiler->compile($template . '.ptl') . '])';
			fwrite($h, $template);
			fclose($h);
			list(,,, $watch) = p::closeMeta();
			p::writeWatchTable($watch, $ctemplate);
		}
		else
		{
			fpassthru($readHandle);
			fclose($readHandle);
		}

		p::setMaxage(-1);
	}

	static function sendPipe()
	{
		$pipe = array_shift($_GET);
		preg_match_all('/[a-zA-Z_0-9\x80-\xff]+/', $pipe, $pipe);
		p::$agentClass = 'agent__pipe/' . implode('_', $pipe[0]);

		foreach ($pipe[0] as &$pipe)
		{
#>			if (DEBUG) call_user_func(array('pipe_' . $pipe, 'js'));
#>			else
#>			{
				$cpipe = p::getContextualCachePath('pipe/' . $pipe, 'js');
				$readHandle = true;
				if ($h = p::fopenX($cpipe, $readHandle))
				{
					ob_start();
					call_user_func(array('pipe_' . $pipe, 'js'));
					$pipe = ob_get_clean();

					$parser = new jsqueez;
					echo $pipe = $parser->squeeze($pipe);

					fwrite($h, $pipe);
					fclose($h);
					p::writeWatchTable(array('pipe'), $cpipe);
				}
				else
				{
					fpassthru($readHandle);
					fclose($readHandle);
				}
#>			}

			echo "\n";
		}

		echo 'w()';

		p::setMaxage(-1);
	}

	static function sendFile($agent, $mime)
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


	protected static $filterRx;

	static function readfile($file, $mime)
	{
		$h = $file;

		if (strlen($h) < 2 || !('/' == $h[0] || ':' == $h[1])) $h = resolvePath($h);

		if (!file_exists($h) || is_dir($h))
		{
			W(__CLASS__ . '::' . __METHOD__ . "(..): invalid file ({$file})");
			return;
		}

		$file = $h;
		$mime || $mime = isset(p::$headers['content-type']) ? substr(p::$headers['content-type'], 14) : 'application/octet-stream';
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

					$range = isset($_SERVER['HTTP_RANGE']) ? patchwork_httpRange::negociate($size, p::$ETag, p::$LastModified) : false;
				}
				else header('Accept-Ranges: none');

				set_time_limit(0);
				ignore_user_abort(false);

				if ($range)
				{
					unset(p::$headers['content-type']);
					patchwork_httpRange::sendChunks($range, $h, $mime, $size);
				}
				else
				{
					$gzip || header('Content-Length: ' . $size);
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

		if (!isset($base))
		{
			$base = dirname($_SERVER['PATCHWORK_REQUEST'] . ' ');
			if (1 === strlen($base) && strspn($base, '/\\.')) $base = '';
			$base = p::__BASE__() . $base . '/';
		}

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

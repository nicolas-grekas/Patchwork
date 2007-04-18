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
}

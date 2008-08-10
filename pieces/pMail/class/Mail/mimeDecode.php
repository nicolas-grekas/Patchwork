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


class extends self
{
	function _decodeHeader($input)
	{
		$input = iconv_mime_decode($input);
		$input = FILTER::get($input, 'text');

		return $input;
	}

	function _decode($headers, $body, $default_ctype = 'text/plain')
	{
		$return = parent::_decode($headers, $body, $default_ctype);

		if (isset($return->body))
		{
			$charset = empty($return->ctype_parameters['charset']) ? false : trim($return->ctype_parameters['charset']);
			$ctype = strtolower(isset($return->ctype_primary) ? $return->ctype_primary . '/' . $return->ctype_secondary : $default_ctype);

			if (!$charset) switch ($ctype)
			{
			case 'text/html':
			case 'text/plain':
				$charset = @iconv('UTF-8', 'UTF-8', $return->body) === $return->body ? 'utf-8' : 'iso-8859-1';
			}

			if ($charset)
			{
				if ('iso-8859-1' === strtolower($charset)) $charset = utf8_encode($return->body);
				else
				{
					$charset = @iconv($charset, 'UTF-8//IGNORE', $return->body);
					false === $charset && $charset = utf8_encode($return->body);
				}

				$return->body = FILTER::get($charset, 'text');
			}
		}

		return $return;
	}
}

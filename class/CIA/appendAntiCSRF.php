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
	static $entitiesRx = "'&(nbsp|iexcl|cent|pound|curren|yen|brvbar|sect|uml|copy|ordf|laquo|not|shy|reg|macr|deg|plusmn|sup2|sup3|acute|micro|para|middot|cedil|sup1|ordm|raquo|frac14|frac12|frac34|iquest|Agrave|Aacute|Acirc|Atilde|Auml|Aring|AElig|Ccedil|Egrave|Eacute|Ecirc|Euml|Igrave|Iacute|Icirc|Iuml|ETH|Ntilde|Ograve|Oacute|Ocirc|Otilde|Ouml|times|Oslash|Ugrave|Uacute|Ucirc|Uuml|Yacute|THORN|szlig|agrave|aacute|acirc|atilde|auml|aring|aelig|ccedil|egrave|eacute|ecirc|euml|igrave|iacute|icirc|iuml|eth|ntilde|ograve|oacute|ocirc|otilde|ouml|divide|oslash|ugrave|uacute|ucirc|uuml|yacute|thorn|yuml|quot|lt|gt|amp|[xX][0-9a-fA-F]+|[0-9]+);'";

	static function call($f)
	{
		$f = $f[0];

		// AntiCSRF token is appended only to local application's form

		// Extract the action attribute
		if (1 < preg_match_all('#\saction\s*=\s*(["\']?)(.*?)\1([^>]*)>#iu', $f, $a, PREG_SET_ORDER)) return $f;

		if ($a)
		{
			$a = $a[0];
			$a = trim($a[1] ? $a[2] : ($a[2] . $a[3]));

			if (0 !== strpos($a, self::$home))
			{
				// Decode html encoded chars
				if (false !== strpos($a, '&')) $a = preg_replace_callback(self::$entitiesRx, array(__CLASS__, 'translateHtmlEntity'), $a);

				// Build absolute URI
				if (preg_match("'^[^:/]*://[^/]*'", $a, $host))
				{
					$host = $host[0];
					$a = substr($a, strlen($host));
				}
				else
				{
					$host = substr(self::$host, 0, -1);

					if ('/' != substr($a, 0, 1))
					{
						static $uri = false;

						if (!$uri)
						{
							$uri = $_SERVER['REQUEST_URI'];

							if (false !== ($b = strpos($uri, '?'))) $uri = substr($uri, 0, $b);

							$uri = dirname($uri . ' ');

							if (
								   ''  === $uri
								|| '/'  == $uri
								|| '\\' == $uri
							)    $uri  = '/';
							else $uri .= '/';
						}

						$a = $uri . $a;
					}
				}

				if (false !== ($b = strpos($a, '?'))) $a = substr($a, 0, $b);
				if (false !== ($b = strpos($a, '#'))) $a = substr($a, 0, $b);

				$a .= '/';

				// Resolve relative paths
				if (false !== strpos($a, './') || false !== strpos($a, '//'))
				{
					$b = $a;

					do
					{
						$a = $b;
						$b = str_replace('/./', '/', $b);
						$b = str_replace('//', '/', $b);
						$b = preg_replace("'/[^/]*[^/\.][^/]*/\.\./'", '/', $b);
					}
					while ($b != $a);
				}

				// Compare action to application's home
				if (0 !== strpos($host . $a, self::$home)) return $f;
			}
		}

		static $appendedHtml = false;

		if (!$appendedHtml)
		{
			$appendedHtml = self::$isServersideHtml ? 'syncCSRF()' : '(function(){var d=document,f=d.forms;f=f[f.length-1].T$.value=d.cookie.match(/(^|; )T\\$=([0-9a-zA-Z]+)/)[2]})()';
			$appendedHtml = '<input type="hidden" name="T$" value="' . (isset($_COOKIE['JS']) && $_COOKIE['JS'] ? '' : $GLOBALS['cia_token']) . '" /><script type="text/javascript">' . "<!--\n{$appendedHtml}//--></script>";
		}

		return $f . $appendedHtml;
	}

	static function translateHtmlEntity($c)
	{
		static $table = false;

		if (!$table) $table = array_flip(get_html_translation_table(HTML_ENTITIES));

		if (isset($table[$c[0]])) return utf8_encode($table[$c[0]]);

		$c = strtolower($c[1]);

		if ('x' == $c[0]) $c = hexdec(substr($c, 1));

		$c = sprinf('%08x', (int) $c);

		if (strlen($c) > 8) return '';

		$r = '';

		do
		{
			$a = substr($c, 0, 2);
			$c = substr($c, 2);

			if ('00' != $a) $r .= chr(hexdec($a));
		}
		while ($c);

		return $r;
	}
}

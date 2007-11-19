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
	protected static $entitiesRx = "'&(nbsp|iexcl|cent|pound|curren|yen|euro|brvbar|sect|[AEIOUYaeiouy]?(?:uml|acute)|copy|ordf|laquo|not|shy|reg|macr|deg|plusmn|sup[123]|micro|para|middot|[Cc]?cedil|ordm|raquo|frac(?:14|12|34)|iquest|[AEIOUaeiou](?:grave|circ)|[ANOano]tilde|[Aa]ring|(?:AE|ae|sz)lig|ETH|times|[Oo]slash|THORN|eth|divide|thorn|quot|lt|gt|amp|[xX][0-9a-fA-F]+|[0-9]+);'";


	static function scriptAlert()
	{
		p::setMaxage(0);
		if (p::$catchMeta) p::$metaInfo[1] = array('private');

		if (PATCHWORK_DIRECT)
		{
			$a = '';

			$cache = p::getContextualCachePath('agentArgs/' . p::$agentClass, 'txt');

			if (file_exists($cache))
			{
				$h = fopen($cache, 'r+b');
				if (!$a = fread($h, 1))
				{
					p::touch('appId');
					p::touch('public/templates/js');

					rewind($h);
					fwrite($h, $a = '1');

					p::touchAppId();
				}

				fclose($h);
			}

			throw new PrivateDetection($a);
		}

		W('Potential JavaScript-Hijacking. Stopping !');

		p::disable(true);
	}

	static function postAlert()
	{
		W('Potential Cross Site Request Forgery. $_POST and $_FILES are not reliable. Erasing !');
	}

	static function appendToken($f)
	{
		$f = $f[0];

		// AntiCSRF token is appended only to local application's form

		// Extract the action attribute
		if (1 < preg_match_all('#\saction\s*=\s*(["\']?)(.*?)\1([^>]*)>#iu', $f, $a, PREG_SET_ORDER)) return $f;

		if ($a)
		{
			$a = $a[0];
			$a = trim($a[1] ? $a[2] : ($a[2] . $a[3]));

			if (0 !== strpos($a, p::$base))
			{
				// Decode html encoded chars
				if (false !== strpos($a, '&')) $a = preg_replace_callback(self::$entitiesRx, array(__CLASS__, 'translateHtmlEntities'), $a);

				// Build absolute URI
				if (preg_match("'^[^:/]*://[^/]*'", $a, $host))
				{
					$host = $host[0];
					$a = substr($a, strlen($host));
				}
				else
				{
					$host = substr(p::$host, 0, -1);

					if ('/' !== substr($a, 0, 1))
					{
						static $uri = false;

						if (!$uri)
						{
							$uri = $_SERVER['REQUEST_URI'];

							if (false !== ($b = strpos($uri, '?'))) $uri = substr($uri, 0, $b);

							$uri = dirname($uri . ' ');

							if (
								   ''   === $uri
								|| '/'  === $uri
								|| '\\' === $uri
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

				// Compare action to application's base
				if (0 !== strpos($host . $a, p::$base)) return $f;
			}
		}

		static $appendedHtml = false;

		if (!$appendedHtml)
		{
			$appendedHtml = !p::$binaryMode ? 'syncCSRF()' : '(function(){var d=document,f=d.forms;f=f[f.length-1].T$.value=d.cookie.match(/(^|; )T\\$=([0-9a-zA-Z]+)/)[2]})()';
			$appendedHtml = '<input type="hidden" name="T$" value="' . (isset($_COOKIE['JS']) && $_COOKIE['JS'] ? '' : self::$antiCSRFtoken) . '" /><script type="text/javascript">' . "<!--\n{$appendedHtml}//--></script>";
		}

		return $f . $appendedHtml;
	}

	protected static function translateHtmlEntities($c)
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

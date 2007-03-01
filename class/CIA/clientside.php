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
	static function loadAgent($agent)
	{
		CIA::setMaxage(-1);
		CIA::setGroup('private');
		CIA::setExpires('onmaxage');

		$cagent = CIA::getContextualCachePath('controler/' . $agent, 'txt', CIA::$versionId);
		$readHandle = true;
		if ($h = CIA::fopenX($cagent, $readHandle))
		{
			$a = CIA::agentArgv($agent);
			array_walk($a, 'jsquoteRef');
			$a = implode(',', $a);

			$agent = jsquote('agent_index' == $agent ? '' : str_replace('_', '/', substr($agent, 6)));

			$lang = CIA::__LANG__();
			$CIApID = CIA::$versionId;
			$home = CIA::__HOME__();

			echo $a =<<<EOHTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="{$lang}">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<script type="text/javascript" name="w$">/*<![CDATA[*/a=[{$agent},[{$a}],{$CIApID}]//]]></script>
<script type="text/javascript" src="{$home}js/w?{$CIApID}"></script>
</html>
EOHTML;

			fwrite($h, $a, strlen($a));
			fclose($h);
			CIA::writeWatchTable('CIApID', $cagent);
		}
		else
		{
			fpassthru($readHandle);
			fclose($readHandle);
		}
	}

	static function render($agent, $liveAgent)
	{
		// Get the calling URI
		if (isset($_COOKIE['R$']))
		{
			CIA::$uri = $_COOKIE['R$'];

			setcookie('R$', false, 0, '/');
	
			// Check the Referer header
			// JS equals 1 when the Referer's confidence is unknown
			//           2 when it is trusted
			if (isset($_COOKIE['JS']) && isset($_SERVER['HTTP_REFERER']) && $_COOKIE['R$'] == $_SERVER['HTTP_REFERER']) setcookie('JS', 2, 0, '/');
		}
		else if (isset($_COOKIE['JS']) && 2 == $_COOKIE['JS'])
		{
			CIA::$uri = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : CIA::$home;
		}

		if ($liveAgent)
		{
			// The output is both html and js, but iframe transport layer needs html
			CIA::$binaryMode = true;
			header('Content-Type: text/html; charset=UTF-8');

			echo '/*<script type="text/javascript">/**/q="';
		}
		else echo 'w(';

		$agentClass = CIA::resolveAgentClass($agent, $_GET);

		CIA::openMeta($agentClass);

		$agent = false;

		try
		{
			if (isset($_GET['T$']) && !CIA_TOKEN_MATCH) throw new PrivateDetection;

			$agent = new $agentClass($_GET);

			$group = CIA::closeGroupStage();

			if ($is_cacheable = !(CIA_POSTING || in_array('private', $group)))
			{
				$cagent = CIA::agentCache($agentClass, $agent->argv, 'js.php', $group);
				$dagent = CIA::getContextualCachePath('jsdata.' . $agentClass, 'js.php', $cagent);

				if ($liveAgent)
				{
					if (file_exists($dagent))
					{
						if (filemtime($dagent)>$_SERVER['REQUEST_TIME'])
						{
							require $dagent;
							CIA::closeMeta();

							echo '"//</script><script type="text/javascript" src="' . CIA::__HOME__() . 'js/QJsrsHandler"></script>';
							return;
						}
						else unlink($dagent);
					}
				}
				else
				{
					if (file_exists($cagent))
					{
						if (filemtime($cagent)>$_SERVER['REQUEST_TIME'])
						{
							require $cagent;
							CIA::closeMeta();
							return;
						}
						else unlink($cagent);
					}
				}
			}

			ob_start();
			++CIA::$ob_level;

			try
			{
				$data = (object) $agent->compose((object) array());

				if (!CIA::$is_enabled)
				{
					CIA::closeMeta();
					return;
				}

				$template = $agent->getTemplate();

				echo '{';

				$comma = '';
				foreach ($data as $key => &$value)
				{
					echo $comma, "'", jsquote($key, false), "':";
					if ($value instanceof loop) self::writeAgent($value);
					else echo jsquote($value);
					$comma = ',';
				}
	
				echo '}';
			}
			catch (PrivateDetection $data)
			{
				ob_end_clean();
				--CIA::$ob_level;
				CIA::closeMeta();
				throw $data;
			}

			$data = ob_get_clean();
			--CIA::$ob_level;

			$agent->metaCompose();
			list($maxage, $group, $expires, $watch, $headers) = CIA::closeMeta();
		}
		catch (PrivateDetection $data)
		{
			if ($liveAgent)
			{
				echo 'false";(window.E||alert)("You must provide an auth token to get this liveAgent:\\n' . jsquote($_SERVER['REQUEST_URI'], false, '"') . '")';
				echo '//</script><script type="text/javascript" src="' . CIA::__HOME__() . 'js/QJsrsHandler"></script>';
			}
			else if ($data->getMessage())
			{
				echo 'w.r(0,' . (int)!DEBUG . '));';
			}
			else
			{
				echo ');window.E&&E("You must provide an auth token to get this agent:\\n' . jsquote($_SERVER['REQUEST_URI'], false, '"') . '")';
			}

			exit;
		}

		if ($liveAgent)
		{
			echo str_replace(array('\\', '"'), array('\\\\', '\\"'), $data),
				'"//</script><script type="text/javascript" src="' . CIA::__HOME__() . 'js/QJsrsHandler"></script>';
		}
		else echo $data;

		if (false !== strpos($data, '<?')) $data = str_replace('<?', '<<?php ?>?', $data);

		if ('ontouch' == $expires && !($watch || CIA_MAXAGE == $maxage)) $expires = 'auto';
		$expires = 'auto' == $expires && ($watch || CIA_MAXAGE == $maxage) ? 'ontouch' : 'onmaxage';

		$is_cacheable = $is_cacheable && !in_array('private', $group) && ($maxage || 'ontouch' == $expires);

		if (!$liveAgent || $is_cacheable)
		{
			if ($is_cacheable) ob_start();

			if (CIA_MAXAGE == $maxage)
			{
				$ctemplate = CIA::getContextualCachePath("templates/$template", 'txt');
				$readHandle = true;
				if ($h = CIA::fopenX($ctemplate, $readHandle))
				{
					CIA::openMeta('agent__template/' . $template, false);
					$compiler = new iaCompiler_js(constant("$agentClass::binary"));
					echo $template = ',[' . $compiler->compile($template . '.tpl') . '])';
					fwrite($h, $template, strlen($template));
					fclose($h);
					list(,,, $template) = CIA::closeMeta();
					CIA::writeWatchTable($template, $ctemplate);
				}
				else
				{
					fpassthru($readHandle);
					fclose($readHandle);
				}

				$watch[] = 'public/templates/js';
			}
			else echo ',[1,"', jsquote(jsquote($template), false, '"'), '",0,0,0])';

			if ($is_cacheable)
			{
				$ob = true;

				if ($h = CIA::fopenX($dagent))
				{
					$template = '<?php CIA::setMaxage(' . (int) $maxage . ");CIA::setExpires('$expires');";

					if ($headers)
					{
						$headers = array_map('addslashes', $headers);
						$template .= "header('" . implode("');header('", $headers) . "');";
					}

					$expires = 'ontouch' == $expires ? CIA_MAXAGE : $maxage;

					$template .= '?>';

					$maxage = $template . str_replace(array('\\', '"'), array('\\\\', '\\"'), $data);

					fwrite($h, $maxage, strlen($maxage));
					fclose($h);

					touch($dagent, $_SERVER['REQUEST_TIME'] + $expires);

					if ($h = CIA::fopenX($cagent))
					{
						$ob = false;
						$maxage = ($liveAgent ? ob_get_clean() : ob_get_flush());
						if (false !== strpos($maxage, '<?')) $maxage = str_replace('<?', '<<?php ?>?', $maxage);
						$maxage = $template . $data . $maxage;

						fwrite($h, $maxage, strlen($maxage));
						fclose($h);

						touch($dagent, $_SERVER['REQUEST_TIME'] + $expires);

						CIA::writeWatchTable($watch, $dagent);
						CIA::writeWatchTable($watch, $cagent);
					}
				}

				if ($ob) $liveAgent ? ob_end_clean() : ob_end_flush();
			}
		}
	}

	protected static function writeAgent(&$loop)
	{
		if (!CIA::string($loop))
		{
			echo 0;
			return;
		}

		echo "w.x([", $loop, ",[";

		$prevKeyList = '';

		while ($data = $loop->loop())
		{
			$data = (array) $data;

			$keyList = array_keys($data);
			array_walk($keyList, 'jsquoteRef');
			$keyList = implode(',', $keyList);

			if ($keyList != $prevKeyList)
			{
				echo $prevKeyList ? '],[' : '',  count($data), ',', $keyList;
				$prevKeyList = $keyList;
			}

			foreach ($data as &$value)
			{
				echo ',';
				if ($value instanceof loop) self::writeAgent($value);
				else echo jsquote($value);
			}
		}

		echo ']])';
	}
}

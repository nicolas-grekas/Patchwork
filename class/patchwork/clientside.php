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


class extends patchwork
{
	static function loadAgent($agent)
	{
		patchwork::setMaxage(-1);
		patchwork::setGroup('private');
		patchwork::setExpires('onmaxage');

		$cagent = patchwork::getContextualCachePath('controler/' . $agent, 'txt', patchwork::$appId);
		$readHandle = true;
		if ($h = patchwork::fopenX($cagent, $readHandle))
		{
			$a = patchwork::agentArgv($agent);
			array_walk($a, 'jsquoteRef');
			$a = implode(',', $a);

			$agent = jsquote('agent_index' == $agent ? '' : str_replace('_', '/', substr($agent, 6)));

			$lang = patchwork::__LANG__();
			$appId = patchwork::$appId;
			$base = patchwork::__BASE__();

			echo $a =<<<EOHTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{$lang}" lang="{$lang}">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<script type="text/javascript" name="w$">/*<![CDATA[*/a=[{$agent},[{$a}],{$appId}]//]]></script>
<script type="text/javascript" src="{$base}js/w?{$appId}"></script>
</html>
EOHTML;

			fwrite($h, $a);
			fclose($h);
			patchwork::writeWatchTable('appId', $cagent);
		}
		else
		{
			fpassthru($readHandle);
			fclose($readHandle);
		}
	}

	static function render($agent, $liveAgent)
	{
		global $CONFIG;
		$config_maxage = $CONFIG['maxage'];

		// Get the calling URI
		if (isset($_COOKIE['R$']))
		{
			patchwork::$uri = $_COOKIE['R$'];

			setcookie('R$', '', 1, '/');
	
			// Check the Referer header
			// T$ starts with 2 when the Referer's confidence is unknown
			//                1 when it is trusted
			if (isset($_SERVER['HTTP_REFERER']) && $_COOKIE['R$'] == $_SERVER['HTTP_REFERER'])
			{
				if (class_exists('SESSION', false))
				{
					$_COOKIE['T$'] = '1';
					SESSION::regenerateId();
				}
				else
				{
					$patchwork_token = $GLOBALS['patchwork_token'];
					$patchwork_token[0] = '1';

					setcookie('T$', $patchwork_token, 0, $CONFIG['session.cookie_path'], $CONFIG['session.cookie_domain']);
				}
			}
		}
		else patchwork::$uri = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : patchwork::$base;

		if ($liveAgent)
		{
			// The output is both html and js, but iframe transport layer needs html
			patchwork::$binaryMode = true;
			header('Content-Type: text/html');

			echo '/*<script type="text/javascript">/**/q="';
		}
		else echo 'w(';

		$agentClass = patchwork::resolveAgentClass($agent, $_GET);

		patchwork::openMeta($agentClass);

		$agent = false;

		try
		{
			if (isset($_GET['T$']) && !PATCHWORK_TOKEN_MATCH) throw new PrivateDetection;

			$agent = new $agentClass($_GET);

			$group = patchwork::closeGroupStage();

			if ($is_cacheable = !(IS_POSTING || in_array('private', $group)))
			{
				$cagent = patchwork::agentCache($agentClass, $agent->argv, 'js.ser', $group);
				$dagent = patchwork::getContextualCachePath('jsdata.' . $agentClass, 'js.ser', $cagent);

				if ($liveAgent)
				{
					if (file_exists($dagent))
					{
						if (filemtime($dagent) > $_SERVER['REQUEST_TIME'])
						{
							$data = unserialize(file_get_contents($dagent));
							patchwork::setMaxage($data['maxage']);
							patchwork::setExpires($data['expires']);
							patchwork::writeWatchTable($data['watch']);
							array_map(array('patchwork', 'header'), $data['headers']);
							patchwork::closeMeta();

							echo str_replace(array('\\', '"', '</'), array('\\\\', '\\"', '<\\/'), $data['rawdata']),
								'"//</script><script type="text/javascript" src="' . patchwork::__BASE__() . 'js/QJsrsHandler"></script>';

							return;
						}
						else
						{
							@unlink($cagent);
							@unlink($dagent);
						}
					}
				}
				else
				{
					if (file_exists($cagent))
					{
						if (filemtime($cagent) > $_SERVER['REQUEST_TIME'])
						{
							$data = unserialize(file_get_contents($cagent));
							patchwork::setMaxage($data['maxage']);
							patchwork::setExpires($data['expires']);
							patchwork::writeWatchTable($data['watch']);
							array_map(array('patchwork', 'header'), $data['headers']);
							patchwork::closeMeta();

							echo $data['rawdata'];

							return;
						}
						else
						{
							@unlink($cagent);
							@unlink($dagent);
						}
					}
				}
			}

			ob_start();
			++patchwork::$ob_level;

			try
			{
				$data = (object) $agent->compose((object) array());

				if (!patchwork::$is_enabled)
				{
					patchwork::closeMeta();
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
				--patchwork::$ob_level;
				patchwork::closeMeta();
				throw $data;
			}

			$data = ob_get_clean();
			--patchwork::$ob_level;

			$agent->metaCompose();
			list($maxage, $group, $expires, $watch, $headers) = patchwork::closeMeta();
		}
		catch (PrivateDetection $data)
		{
			if ($liveAgent)
			{
				echo 'false";(window.E||alert)("You must provide an auth token to get this liveAgent:\\n' . jsquote($_SERVER['REQUEST_URI'], false, '"') . '")';
				echo '//</script><script type="text/javascript" src="' . patchwork::__BASE__() . 'js/QJsrsHandler"></script>';
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
			echo str_replace(array('\\', '"', '</'), array('\\\\', '\\"', '<\\/'), $data),
				'"//</script><script type="text/javascript" src="' . patchwork::__BASE__() . 'js/QJsrsHandler"></script>';
		}
		else echo $data;

		if ('ontouch' == $expires && !($watch || $config_maxage == $maxage)) $expires = 'auto';
		$expires = 'auto' == $expires && ($watch || $config_maxage == $maxage) ? 'ontouch' : 'onmaxage';

		$is_cacheable = $is_cacheable && !in_array('private', $group) && ($maxage || 'ontouch' == $expires);

		if (!$liveAgent || $is_cacheable)
		{
			if ($is_cacheable) ob_start();

			if ($maxage == $config_maxage && PATCHWORK_TURBO)
			{
				$ctemplate = patchwork::getContextualCachePath("templates/$template", 'txt');

				patchwork::syncTemplate($template, $ctemplate);

				$readHandle = true;

				if ($h = patchwork::fopenX($ctemplate, $readHandle))
				{
					patchwork::openMeta('agent__template/' . $template, false);
					$compiler = new ptlCompiler_js(patchwork::$binaryMode);
					echo $template = ',[' . $compiler->compile($template . '.ptl') . '])';
					fwrite($h, $template);
					fclose($h);
					list(,,, $template) = patchwork::closeMeta();
					patchwork::writeWatchTable($template, $ctemplate);
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

				$template = array(
					'maxage' => $maxage,
					'expires' => $expires,
					'watch'   => $watch,
					'headers' => $headers,
					'rawdata' => $data,
				);

				$expires = 'ontouch' == $expires ? $config_maxage : $maxage;

				if ($h = patchwork::fopenX($dagent))
				{
					fwrite($h, serialize($template));
					fclose($h);

					touch($dagent, $_SERVER['REQUEST_TIME'] + $expires);

					patchwork::writeWatchTable($watch, $dagent);
				}

				if ($h = patchwork::fopenX($cagent))
				{
					$ob = false;
					$template['rawdata'] .= $liveAgent ? ob_get_clean() : ob_get_flush();

					fwrite($h, serialize($template));
					fclose($h);

					touch($cagent, $_SERVER['REQUEST_TIME'] + $expires);

					patchwork::writeWatchTable($watch, $cagent);
				}

				if ($ob) $liveAgent ? ob_end_clean() : ob_end_flush();
			}
		}
	}

	protected static function writeAgent(&$loop)
	{
		if (!patchwork::string($loop))
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

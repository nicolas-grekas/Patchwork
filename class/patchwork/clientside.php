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
	static function loadAgent($agent)
	{
#>		p::touch('debugSync');

		p::setMaxage(-1);
		p::setPrivate();
		p::setExpires('onmaxage');

		$a = p::agentArgs($agent);
		array_walk($a, 'jsquoteRef');
		$a = implode(',', $a);

		$agent = jsquote('agent_index' === $agent ? '' : str_replace('·', '.', strtr(substr($agent, 6), '_', '/')));

		$lang = p::__LANG__();
		$appId = p::$appId;
		$base = p::__BASE__();

		if (PATCHWORK_I18N)
		{
			ob_start();
			self::writeAgent(new loop_altLang);
			$b = substr(ob_get_clean(), 4, -1);
		}
		else $b = '0';

		$lang = $lang ? " xml:lang=\"{$lang}\" lang=\"{$lang}\"" : '';

		echo $a =<<<EOHTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"{$lang}>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="text/javascript" name="w$">/*<![CDATA[*/a=[{$agent},[{$a}],{$appId},{$b}]//]]></script>
<script type="text/javascript" src="{$base}js/w?{$appId}"></script>
</html>
EOHTML;
	}

	static function render($agent, $liveAgent)
	{
		$config_maxage = $CONFIG['maxage'];

		// Get the calling URI
		if (isset($_COOKIE['R$']))
		{
			p::$uri = $_COOKIE['R$'];

			setcookie('R$', '', 1, '/');

			// Check the Referer header
			// T$ starts with 2 when the Referer's confidence is unknown
			//                1 when it is trusted
			if (isset($_SERVER['HTTP_REFERER']) && $_COOKIE['R$'] === $_SERVER['HTTP_REFERER'])
			{
				if (class_exists('SESSION', false))
				{
					$_COOKIE['T$'] = '1';
					SESSION::regenerateId();
				}
				else
				{
					self::$antiCSRFtoken[0] = '1';
					setcookie('T$', self::$antiCSRFtoken, 0, $CONFIG['session.cookie_path'], $CONFIG['session.cookie_domain']);
				}
			}
		}
		else p::$uri = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : p::$base;

		if ($liveAgent)
		{
			// The output is both html and js, but iframe transport layer needs html
			p::$binaryMode = true;
			header('Content-Type: text/html');

			echo '/*<script type="text/javascript">/**/q="';
		}
		else echo 'w(';

		$agentClass = p::resolveAgentClass($agent, $_GET);

		p::openMeta($agentClass);

		$agent = false;

		try
		{
			if (isset($_GET['T$']) && !PATCHWORK_TOKEN_MATCH) throw new PrivateDetection;

			$agent = new $agentClass($_GET);

			$group = p::closeGroupStage();

			if ($is_cacheable = !(IS_POSTING || in_array('private', $group)))
			{
				$cagent = p::agentCache($agentClass, $agent->get, 'js.ser', $group);
				$dagent = p::getContextualCachePath('jsdata.' . $agentClass, 'js.ser', $cagent);

				if ($liveAgent)
				{
					if (file_exists($dagent))
					{
						if (filemtime($dagent) > $_SERVER['REQUEST_TIME'])
						{
							$data = unserialize(file_get_contents($dagent));
							p::setMaxage($data['maxage']);
							p::setExpires($data['expires']);
							p::writeWatchTable($data['watch']);
							array_map(array('patchwork', 'header'), $data['headers']);
							p::closeMeta();

							echo str_replace(array('\\', '"', '</'), array('\\\\', '\\"', '<\\/'), $data['rawdata']),
								'"//</script><script type="text/javascript" src="' . p::__BASE__() . 'js/QJsrsHandler"></script>';

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
							p::setMaxage($data['maxage']);
							p::setExpires($data['expires']);
							p::writeWatchTable($data['watch']);
							array_map(array('patchwork', 'header'), $data['headers']);
							p::closeMeta();

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
			++p::$ob_level;

			try
			{
				$data = (object) $agent->compose((object) array());

				if (!p::$is_enabled)
				{
					p::closeMeta();
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
				--p::$ob_level;
				p::closeMeta();
				throw $data;
			}

			$data = ob_get_clean();
			--p::$ob_level;

			$agent->metaCompose();
			list($maxage, $group, $expires, $watch, $headers) = p::closeMeta();
		}
		catch (PrivateDetection $data)
		{
			if ($liveAgent)
			{
				echo 'false";(window.E||alert)("You must provide an auth token to get this liveAgent:\\n' . jsquote($_SERVER['REQUEST_URI'], false, '"') . '")';
				echo '//</script><script type="text/javascript" src="' . p::__BASE__() . 'js/QJsrsHandler"></script>';
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
				'"//</script><script type="text/javascript" src="' . p::__BASE__() . 'js/QJsrsHandler"></script>';
		}
		else echo $data;

		if ('ontouch' === $expires && !($watch || $config_maxage == $maxage)) $expires = 'auto';
		$expires = 'auto' === $expires && ($watch || $config_maxage == $maxage) ? 'ontouch' : 'onmaxage';

		$is_cacheable = $is_cacheable && !in_array('private', $group) && ($maxage || 'ontouch' === $expires);

		if (!$liveAgent || $is_cacheable)
		{
			if ($is_cacheable) ob_start();

			if ($config_maxage == $maxage && TURBO)
			{
				$ctemplate = p::getContextualCachePath("templates/$template", 'txt');

				$readHandle = true;

				if ($h = p::fopenX($ctemplate, $readHandle))
				{
					p::openMeta('agent__template/' . $template, false);
					$compiler = new ptlCompiler_js(p::$binaryMode);
					echo $template = ',[' . $compiler->compile($template) . '])';
					fwrite($h, $template);
					fclose($h);
					list(,,, $template) = p::closeMeta();
					p::writeWatchTable($template, $ctemplate);
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

				$expires = 'ontouch' === $expires ? $config_maxage : $maxage;

				if ($h = p::fopenX($dagent))
				{
					fwrite($h, serialize($template));
					fclose($h);

					touch($dagent, $_SERVER['REQUEST_TIME'] + $expires);

					p::writeWatchTable($watch, $dagent);
				}

				if ($h = p::fopenX($cagent))
				{
					$ob = false;
					$template['rawdata'] .= $liveAgent ? ob_get_clean() : ob_get_flush();

					fwrite($h, serialize($template));
					fclose($h);

					touch($cagent, $_SERVER['REQUEST_TIME'] + $expires);

					p::writeWatchTable($watch, $cagent);
				}

				if ($ob) $liveAgent ? ob_end_clean() : ob_end_flush();
			}
		}
	}

	protected static function writeAgent($loop)
	{
		if (!p::string($loop))
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

			if ($keyList !== $prevKeyList)
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

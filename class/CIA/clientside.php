<?php

class extends CIA
{
	public static function loadAgent($agent)
	{
		self::setMaxage(-1);
		self::setGroup('private');
		self::setExpires('onmaxage');

		$cagent = self::getContextualCachePath('controler/' . $agent, 'txt', CIA_PROJECT_ID);

		if ($h = self::fopenX($cagent))
		{
			$a = self::agentArgv($agent);
			array_walk($a, 'jsquoteRef');
			$a = implode(',', $a);

			$agent = jsquote('agent_index' == $agent ? '' : str_replace('_', '/', substr($agent, 6)));

			$lang = self::__LANG__();
			$CIApID = CIA_PROJECT_ID;
			$home = self::__HOME__();

			echo $a =<<<EOHTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="{$lang}">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<script type="text/javascript" class="w">/*<![CDATA[*/a=[{$agent},[{$a}],{$CIApID}]//]]></script>
<script type="text/javascript" src="{$home}js/w"></script>
</html>
EOHTML;

			fwrite($h, $a, strlen($a));
			fclose($h);
			self::writeWatchTable('CIApID', $cagent);
		}
		else readfile($cagent);
	}

	public static function render($agent, $liveAgent)
	{
		if ($liveAgent)
		{
			// The output is both html and js, but iframe transport layer needs html
			self::header('Content-Type: text/html; charset=UTF-8');

			echo '/*<script type="text/javascript">/**/q="';
		}
		else echo 'w(';

		$agentClass = self::resolveAgentClass($agent, $_GET);

		self::openMeta($agentClass);

		$agent = false;

		try
		{
			$agent = new $agentClass($_GET);

			$group = self::closeGroupStage();

			if ($is_cacheable = !in_array('private', $group))
			{
				$cagent = self::agentCache($agentClass, $agent->argv, 'js.php', $group);
				$dagent = self::getContextualCachePath('jsdata.' . $agentClass, 'js.php', $cagent);

				if ($liveAgent)
				{
					if (file_exists($dagent))
					{
						if (filemtime($dagent)>$_SERVER['REQUEST_TIME'])
						{
							require $dagent;
							self::closeMeta();

							echo '"//</script><script type="text/javascript" src="' . self::__HOME__() . 'js/QJsrsHandler"></script>';
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
							self::closeMeta();
							return;
						}
						else unlink($cagent);
					}
				}
			}

			ob_start();

			$data = (object) $agent->compose((object) array());
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

			$agent->metaCompose();
			list($maxage, $group, $expires, $watch, $headers) = self::closeMeta();
		}
		catch (PrivateDetection $data)
		{
			if ($agent) @ob_clean();

			if ($liveAgent)
			{
				echo 'false";(window.E||alert)("You must provide an auth token to get this liveAgent:\\n' . jsquote($_SERVER['REQUEST_URI'], false, '"') . '")';
				echo '//</script><script type="text/javascript" src="' . self::__HOME__() . 'js/QJsrsHandler"></script>';
			}
			else if ($data->getMessage())
			{
				echo 'location.reload(' . (DEBUG ? '' : 'true') . '))';
			}
			else
			{
				echo '{},[]);window.E&&E("You must provide an auth token to get this agent:\\n' . jsquote($_SERVER['REQUEST_URI'], false, '"') . '")';
			}

			exit;
		}

		if ($liveAgent)
		{
			$data = @ob_get_clean();
			echo str_replace(array('\\', '"'), array('\\\\', '\\"'), $data),
				'"//</script><script type="text/javascript" src="' . self::__HOME__() . 'js/QJsrsHandler"></script>';
		}
		else $data = @ob_get_flush();

		$data = str_replace('<?', "<<?php ?>?", $data);

		if ('ontouch' == $expires && !($watch || CIA_MAXAGE == $maxage)) $expires = 'auto';
		$expires = 'auto' == $expires && ($watch || CIA_MAXAGE == $maxage) ? 'ontouch' : 'onmaxage';

		$is_cacheable = $is_cacheable && !in_array('private', $group) && ($maxage || 'ontouch' == $expires);

		if (!$liveAgent || $is_cacheable)
		{
			if ($is_cacheable) ob_start();

			if (CIA_MAXAGE == $maxage)
			{
				$ctemplate = self::getContextualCachePath("templates/$template", 'txt');
				if ($h = self::fopenX($ctemplate))
				{
					self::openMeta('agent__template/' . $template, false);
					$compiler = new iaCompiler_js(constant("$agentClass::binary"));
					echo $template = ',[' . $compiler->compile($template . '.tpl') . '])';
					fwrite($h, $template, strlen($template));
					fclose($h);
					list(,,, $template) = self::closeMeta();
					self::writeWatchTable($template, $ctemplate);
				}
				else readfile($ctemplate);

				$watch[] = 'public/templates/js';
			}
			else echo ',[1,"', jsquote(jsquote($template), false, '"'), '",0,0,0])';

			if ($is_cacheable)
			{
				$ob = true;

				if ($h = self::fopenX($dagent))
				{
					$template = '<?php self::setMaxage(' . (int) $maxage . ");self::setExpires('$expires');";

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

					if ($h = self::fopenX($cagent))
					{
						$ob = false;
						$maxage = $template . $data . str_replace('<?', "<<?php ?>?", $liveAgent ? @ob_get_clean() : @ob_get_flush());

						fwrite($h, $maxage, strlen($maxage));
						fclose($h);

						touch($dagent, $_SERVER['REQUEST_TIME'] + $expires);

						self::writeWatchTable($watch, $dagent);
						self::writeWatchTable($watch, $cagent);
					}
				}

				if ($ob) $liveAgent ? @ob_clean() : @ob_flush();
			}
		}
	}

	private static function writeAgent(&$loop)
	{
		if (!self::string($loop))
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

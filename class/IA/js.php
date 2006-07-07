<?php

class IA_js
{
	public static function loadAgent($agent)
	{
		CIA::setMaxage(-1);
		CIA::setGroup('private');
		CIA::setExpires('onmaxage');

		$cagent = CIA::makeCacheDir('controler/' . $agent, 'txt', CIA_PROJECT_ID);

		if (file_exists($cagent)) readfile($cagent);
		else
		{
			$a = CIA::agentArgv($agent);
			array_walk($a, 'jsquoteRef');
			$a = implode(',', $a);

			$agent = jsquote('agent_index' == $agent ? '' : str_replace('_', '/', substr($agent, 6)));

			$lang = CIA::__LANG__();
			$CIApID = CIA_PROJECT_ID;
			$home = CIA::__HOME__();

			echo $a =<<<EOHTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="{$lang}">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<script type="text/javascript" class="w">/*<![CDATA[*/a=[{$agent},[{$a}],{$CIApID}]//]]></script>
<script type="text/javascript" src="{$home}js/w"></script>
</html>
EOHTML;

			CIA::writeFile($cagent, $a);
			CIA::writeWatchTable('CIApID', $cagent);
		}
	}

	public static function render($agent, $liveAgent)
	{
		if ($liveAgent)
		{
			// The output is both html and js, but iframe transport layer needs html
			CIA::header('Content-Type: text/html; charset=UTF-8');

			echo '/*<script type="text/javascript">/**/q="';
		}
		else echo 'w(';

		$agentClass = CIA::resolveAgentClass($agent, $_GET);

		CIA::openMeta($agentClass);

		$agent = false;

		try
		{
			$agent = new $agentClass($_GET);

			$group = CIA::closeGroupStage();
		
			if ($is_cacheable = !in_array('private', $group))
			{
				$cagent = CIA::agentCache($agentClass, $agent->argv, 'js.php', $group);
				$dagent = CIA::makeCacheDir('jsdata.' . $agentClass, 'js.php', $cagent);
			}

			if ($liveAgent)
			{
				if ($is_cacheable && file_exists($dagent) && filemtime($dagent)>CIA_TIME)
					{
					require $dagent;
					CIA::closeMeta();

					echo '"//</script><script type="text/javascript" src="' . CIA::__HOME__() . 'js/QJsrsHandler"></script>';
					return;
				}
			}
			else
			{
				if ($is_cacheable && file_exists($cagent) && filemtime($cagent)>CIA_TIME)
				{
					require $cagent;
					CIA::closeMeta();
					return;
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
			list($maxage, $group, $expires, $watch, $headers) = CIA::closeMeta();
		}
		catch (PrivateDetection $data)
		{
			if ($agent) ob_clean();

			if ($liveAgent)
			{
				echo 'false";(window.E||alert)("You must provide an auth token to get this liveAgent:\\n' . jsquote($_SERVER['REQUEST_URI'], false, '"') . '")';
				echo '//</script><script type="text/javascript" src="' . CIA::__HOME__() . 'js/QJsrsHandler"></script>';
			}
			else if ($data->getMessage())
			{
				echo 'location.reload(' . (DEBUG ? '' : 'true') . '))';
			}
			else
			{
				echo '{},[]);window.E&&E("You must provide an auth token to get this liveAgent:\\n' . jsquote($_SERVER['REQUEST_URI'], false, '"') . '")';
			}

			exit;
		}

		if ($liveAgent)
		{
			$data = ob_get_clean();
			echo str_replace(array('\\', '"'), array('\\\\', '\\"'), $data),
				'"//</script><script type="text/javascript" src="' . CIA::__HOME__() . 'js/QJsrsHandler"></script>';
		}
		else $data = ob_get_flush();

		$data = str_replace('<?', "<<?php echo'?'?>", $data);

		if ('ontouch' == $expires && !($watch || CIA_MAXAGE == $maxage)) $expires = 'auto';
		$expires = 'auto' == $expires && ($watch || CIA_MAXAGE == $maxage) ? 'ontouch' : 'onmaxage';

		$is_cacheable = $is_cacheable && !in_array('private', $group) && ($maxage || 'ontouch' == $expires);

		if (!$liveAgent || $is_cacheable)
		{
			if ($is_cacheable) ob_start();

			if (CIA_MAXAGE == $maxage)
			{
				$ctemplate = CIA::makeCacheDir("templates/$template", 'txt');
				if (file_exists($ctemplate)) readfile($ctemplate);
				else
				{
					CIA::openMeta('agent__template/' . $template, false);
					$compiler = new iaCompiler_js(constant("$agentClass::binary"));
					echo $template = ',[' . $compiler->compile($template . '.tpl') . '])';
					CIA::writeFile($ctemplate, $template);
					list(,,, $template) = CIA::closeMeta();
					CIA::writeWatchTable($template, $ctemplate);
				}

				$watch[] = 'public/templates/js';
			}
			else echo ',[1,"', jsquote(jsquote($template), false, '"'), '",0,0,0])';

			if ($is_cacheable)
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
				CIA::writeFile($dagent, $maxage, $expires);
				CIA::writeWatchTable($watch, $dagent);

				$maxage = $template . $data . str_replace('<?', "<<?php echo'?'?>", $liveAgent ? ob_get_clean() : ob_get_flush());
				CIA::writeFile($cagent, $maxage, $expires);
				CIA::writeWatchTable($watch, $cagent);
			}
		}
	}

	private static function writeAgent(&$loop)
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

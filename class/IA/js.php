<?php

class IA_js
{
	private static $html = false;

	public static function loadAgent($agent)
	{
		CIA::setMaxage(-1);
		CIA::setPrivate(true);

		$cagent = CIA::makeCacheDir('controler/', 'txt', $agent);

		if (file_exists($cagent)) readfile($cagent);
		else
		{
			self::$html = true;

			$a = CIA::agentArgv($agent);
			array_walk($a, array('self', 'formatJs'));
			$a = implode(',', $a);

			$agent = 'agent_index' == $agent ? '' : str_replace('_', '/', substr($agent, 6));

			echo $a = '<html><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><script type="text/javascript">/*<![CDATA[*/a=['
				. self::formatJs($agent) . ',[' . $a . '],' . CIA_PROJECT_ID . ']/*]]>*/</script><script type="text/javascript" src="'
				. CIA::__ROOT__() . 'js/w"></script></html>';

			CIA::writeFile($cagent, $a);
		}
	}

	public static function compose($agent)
	{
		if (!self::$html) CIA::header('Content-Type: text/javascript; charset=UTF-8');

		echo 'w({';


		$agentClass = CIA::resolveAgentClass($agent, $_GET);

		CIA::openMeta($agentClass);

		$agent = new $agentClass($_GET);

		$cagent = CIA::agentCache($agentClass, $agent->argv, 'js');
		if (file_exists($cagent) && filemtime($cagent)>CIA_TIME)
		{
			require $cagent;
			CIA::closeMeta();
			return;
		}

		$data = $agent->compose();
		$template = $agent->getTemplate();

		ob_start();

		$comma = '';
		foreach ($data as $key => $value)
		{
			echo $comma, "'", self::formatJs($key, false, "'", false), "'", ':';
			if ($value instanceof loop) self::writeAgent($value);
			else echo self::formatJs($value);
			$comma = ',';
		}

		echo '}';

		$agent->metaCompose();
		list($maxage, $private, $expires, $watch, $headers) = CIA::closeMeta();

		if ($maxage==CIA_MAXAGE)
		{
			$ctemplate = CIA::makeCacheDir("templates/$template", 'txt');
			if (file_exists($ctemplate)) readfile($ctemplate);
			else
			{
				CIA::openMeta('agent__template/' . $template, false);
				$compiler = new iaCompiler_js($agent->binary);
				echo $template = ',[' . $compiler->compile($template . '.tpl') . '])';
				CIA::writeFile($ctemplate, $template);
				list(,,, $template) = CIA::closeMeta();
				CIA::writeWatchTable($template, $ctemplate);
				$watch += $template;
			}
		}
		else echo ',[1,"', self::formatJs(self::formatJs($template), false, '"', false), '",0,0,0])';

		if (!$private && ($maxage || ('ontouch' == $expires && $watch)))
		{
			$cagent = CIA::agentCache($agentClass, $agent->argv, 'js');
			$data = '<?php echo ' . var_export(ob_get_flush(), true)
				. ';CIA::setMaxage(' . (int) $maxage . ');'
				. ('ontouch' != $expires ? 'CIA::setExpires("onmaxage");' : '');

			if ($headers)
			{
				$headers = array_map('addslashes', $headers);
				$data .= "header('" . implode("');header('", $headers) . "');";
			}

			CIA::writeFile($cagent, $data, 'ontouch' == $expires && $watch ? CIA_MAXAGE : $maxage);

			CIA::writeWatchTable($watch, $cagent);
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

		while ($data = $loop->compose())
		{
			$data = (array) $data;

			$keyList = array_keys($data);
			array_walk($keyList, array('self', 'formatJs'));
			$keyList = implode(',', $keyList);

			if ($keyList != $prevKeyList)
			{
				echo $prevKeyList ? '],[' : '',  count($data), ',', $keyList;
				$prevKeyList = $keyList;
			}

			foreach ($data as $value)
			{
				echo ',';
				if ($value instanceof loop) self::writeAgent($value);
				else echo self::formatJs($value);
			}
		}

		echo ']])';
	}

	public static function formatJs(&$a, $key = false, $delim = "'", $addDelim = true)
	{
		if ((string) $a === (string) ($a-0)) return $a;

		$a = str_replace(
			array("\r\n", "\r", '\\'  , "\n", $delim),
			array("\n"  , "\n", '\\\\', '\n', '\\' . $delim),
			$a
		);

		if (self::$html) $a = preg_replace("#<(/?script)#iu", '<\\\\$1', $a);

		if ($addDelim) $a = $delim . $a . $delim;

		return $a;
	}
}

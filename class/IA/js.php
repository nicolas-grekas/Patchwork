<?php

class IA_js
{
	private static $html = false;

	public static function loadAgent($agent)
	{
		CIA::setMaxage(-1);
		CIA::setPrivate(true);

		$a = CIA::agentClass($agent);
		$cagent = CIA::makeCacheDir('controler/', 'txt', $a);

		if (file_exists($cagent)) readfile($cagent);
		else
		{
			self::$html = true;

			$a = CIA::agentArgv($a);
			array_walk($a, array('self', 'formatJs'));
			$a = implode(',', $a);

			echo $a = '<html><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><script type="text/javascript">a=['
				. self::formatJs($agent) . ',[' . $a . '],' . CIA_PROJECT_ID . ']</script><script type="text/javascript" src="'
				. htmlspecialchars(CIA_ROOT) . 'js/w"></script></html>';

			CIA::writeFile($cagent, $a);
		}
	}

	public static function compose($agent)
	{
		if (!self::$html) CIA::header('Content-Type: text/javascript; charset=UTF-8');

		echo 'w({';

		CIA::openMeta();

		$agentClass = CIA::agentClass($agent);
		$agent = class_exists($agentClass) ? new $agentClass($_GET) : new agentTemplate_(array('template' => $agent));

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

		CIA::$catchMeta = true;

		$agent->metaCompose();
		list($maxage, $private, $expires, $watch, $headers) = CIA::closeMeta();

		if ($maxage==CIA_MAXAGE)
		{
			$ctemplate = CIA::makeCacheDir("templates/$template", 'txt');
			if (file_exists($ctemplate)) readfile($ctemplate);
			else
			{
				$compiler = new iaCompiler_js($agent->binary);
				echo $template = ',[' . $compiler->compile($template . '.tpl') . '])';
				CIA::writeFile($ctemplate, $template);
				CIA::writeWatchTable(array('public/templates'), $ctemplate);
			}
		}
		else echo ',[1,"g.__ROOT__+', self::formatJs(self::formatJs($template = '_?t=' . $template), false, '"', false), '",0,0,0])';

		if (!$private && ($maxage || ('ontouch' == $expires && $watch)))
		{
			$cagent = CIA::agentCache($agentClass, $agent->argv, 'js');
			$data = '<?php echo ' . var_export(ob_get_flush(), true)
				. ';CIA::setMaxage(' . (int) $maxage . ');'
				. ('ontouch' != $expires ? 'CIA::setExpires("onmaxage");' : '')
				. ($headers ? "header('" . addslashes(implode("\n", $headers)) . "');" : '');
			CIA::writeFile($cagent, $data, 'ontouch' == $expires && $watch ? CIA_MAXAGE : $maxage);

			if ($maxage==CIA_MAXAGE) $watch[] = 'public/templates';
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

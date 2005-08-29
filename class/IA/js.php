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

			$a = get_class_vars($a);
			$a = array_map(array('self','formatJs'), $a['argv']);
			$a = implode(',', $a);

			echo $a = '<html><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><script>a=[' . self::formatJs($agent) . ',[' . $a . ']]</script><script src="' . CIA::htmlescape(CIA_ROOT) . 'js/w"></script></html>';

			CIA::writeFile($cagent, $a);
		}
	}

	public static function render($agent)
	{
		if (!self::$html) CIA::header('Content-Type: text/javascript; charset=UTF-8');

		echo 'CIApID=', CIA_PROJECT_ID, ';w({';

		CIA::openMeta();

		$agentClass = CIA::agentClass($agent);
		$agent = class_exists($agentClass) ? new $agentClass($_GET) : new agentTemplate_(array('template' => $agent));

		$cagent = CIA::agentCache($agentClass, $agent->argv);
		if (file_exists($cagent) && filemtime($cagent)>CIA_TIME)
		{
			require $cagent;
			CIA::closeMeta();
			return;
		}

		$data = $agent->render();
		$template = $agent->getTemplate();

		CIA::$catchMeta = false;

		ob_start();

		$comma = '';
		foreach ($data as $key => $value)
		{
			echo $comma, "'", self::formatJs($key, "'", false), "'", ':';
			if ($value instanceof loop) self::writeAgent($value);
			else echo self::formatJs($value);
			$comma = ',';
		}

		echo '}';

		CIA::$catchMeta = true;

		$agent->postRender();
		list($maxage, $private, $expires, $watch, $headers) = CIA::closeMeta();

		if ($maxage==CIA_MAXAGE && !$expires)
		{
			$ctemplate = CIA::makeCacheDir("templates/$template", 'txt');
			if (file_exists($ctemplate)) readfile($ctemplate);
			else
			{
				$compiler = new iaCompiler_js;
				echo $template = ',[' . $compiler->compile($template . '.tpl') . '])';
				CIA::writeFile($ctemplate, $template);
				CIA::writeWatchTable(array('public/templates'), $ctemplate);
			}
		}
		else echo ',[1,"g.__ROOT__+', self::formatJs(self::formatJs("_?t=$template"), '"', false), '",0,0])';

		if (!$private && ($maxage || !$expires))
		{
			$cagent = CIA::agentCache($agentClass, $agent->argv);
			$data = '<?php echo ' . var_export(ob_get_flush(), true)
				. ';CIA::setMaxage(' . (int) $maxage . ');'
				. ($expires ? 'CIA::setExpires(true);' : '')
				. ($headers ? "header('" . addslashes(implode("\n", $headers)) . "');" : '');
			CIA::writeFile($cagent, $data, $expires ? $maxage : CIA_MAXAGE);

			if ($maxage==CIA_MAXAGE && !$expires) $watch[] = 'public/templates';
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

		while ($data = $loop->render())
		{
			$data = (array) $data;
			$keyList = array_keys($data);
			$keyList = array_map(array('self','formatJs'), $keyList);
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

	private static function formatJs($a, $delim = "'", $addDelim = true)
	{
		if ((string) $a === (string) ($a-0)) return $a;

		$a = str_replace(
			array("\r\n", "\r", '\\',   "\n", $delim),
			array("\n"  , "\n", '\\\\', '\n', '\\' . $delim),
			$a
		);

		if (self::$html) $a = preg_replace("#<(/?script)#iu", '<\\\\$1', $a);

		return $addDelim ? $delim . $a . $delim : $a;
	}
}

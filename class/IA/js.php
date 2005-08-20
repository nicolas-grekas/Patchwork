<?php

class IA_js
{
	private static $html = false;

	public static function loadAgent($agent)
	{
		CIA::setCacheControl(-1, true, false);

		$a = CIA::agentClass($agent);
		$cagent = CIA::makeCacheDir('controler/', 'txt', $a);

		if (file_exists($cagent)) readfile($cagent);
		else
		{
			self::$html = true;

			$a = get_class_vars($a);
			$a = array_map(array('self','formatJs'), $a['argv']);
			$a = implode(',', $a);

			echo $a = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><script>a=[' . self::formatJs($agent) . ',[' . $a . ']]</script><script src="' . CIA::htmlescape(CIA_ROOT) . 'js/w"></script></head></html>';

			CIA::writeFile($cagent, $a);
		}
	}

	public static function render($agent)
	{
		CIA::header('Content-Type: text/javascript; charset=UTF-8');

		echo 'CIApID=', CIA_PROJECT_ID, ';w({';

		$agentClass = CIA::agentClass($agent);
		$agent = class_exists($agentClass) ? new $agentClass($_GET) : new agentTemplate_(array('template' => $agent));

		/* Get agent's data */
		$data = $agent->render();

		/* Output agent's data in JavaScript */
		$comma = '';
		foreach ($data as $key => $value)
		{
			echo $comma, "'", self::formatJs($key, "'", false), "'", ':';
			if ($value instanceof loop) self::writeAgent($value);
			else echo self::formatJs($value);
			$comma = ',';
		}
		echo '}';
		
		/* Get Cache-Control directives */
		$maxage = ;
		$expires = ;

		/* Append template data */
		$template = $agent->getTemplate();
		if ($maxage<0 && !$expires)
		{
			$ctemplate = CIA::makeCacheDir("templates/$template", 'txt');
			if (file_exists($ctemplate)) readfile($ctemplate);
			else
			{
				$compiler = new iaCompiler_js;
				echo $template = ',[' . $compiler->compile($template . '.tpl') . '])';
				CIA::writeFile($ctemplate, $template);
				CIA::watch('public/templates', $ctemplate);
			}

			CIA::watch('public/templates', $cagent);
		}
		else echo ',[1,"g.__ROOT__+', self::formatJs(self::formatJs("_?t=$template"), '"', false), '",0,0])';
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

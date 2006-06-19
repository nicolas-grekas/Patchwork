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
<script type="text/javascript" class="w">/*<![CDATA[*/a=[{$agent},[{$a}],{$CIApID}]/*]]></script>
<script type="text/javascript" src="{$home}js/w"></script>
</html>
EOHTML;

			CIA::writeFile($cagent, $a);
			CIA::writeWatchTable('CIApID', $cagent);
		}
	}

	public static function compose($agent)
	{
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

		ob_start();

		$data = (object) $agent->compose();
		$template = $agent->getTemplate();

		echo 'w({';

		$comma = '';
		foreach ($data as $key => $value)
		{
			echo $comma, "'", jsquote($key, false), "':";
			if ($value instanceof loop) self::writeAgent($value);
			else echo jsquote($value);
			$comma = ',';
		}

		echo '}';

		$agent->metaCompose();
		list($maxage, $group, $expires, $watch, $headers) = CIA::closeMeta();

		if ($maxage==CIA_MAXAGE)
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
				$watch = array_merge($watch, $template);
			}
		}
		else echo ',[1,"', jsquote(jsquote($template), false, '"'), '",0,0,0])';

		if ('ontouch' == $expires && !$watch) $expires = 'auto';
		$expires = 'auto' == $expires && $watch ? 'ontouch' : 'onmaxage';

		if (!in_array('private', $group) && ($maxage || 'ontouch' == $expires))
		{
			$cagent = CIA::agentCache($agentClass, $agent->argv, 'js', $group);

			$data = str_replace('<?', "<?php echo'<?'?>", ob_get_flush())
				. '<?php CIA::setMaxage(' . (int) $maxage
				. ");CIA::setExpires('$expires');";

			if ($headers)
			{
				$headers = array_map('addslashes', $headers);
				$data .= "header('" . implode("');header('", $headers) . "');";
			}

			CIA::writeFile($cagent, $data, 'ontouch' == $expires ? CIA_MAXAGE : $maxage);

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
			array_walk($keyList, 'jsquoteRef');
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
				else echo jsquote($value);
			}
		}

		echo ']])';
	}
}

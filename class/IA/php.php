<?php

class IA_php
{
	protected static $args;
	protected static $values;
	protected static $get;

	protected static $cache = array();

	public static function loadAgent($agent, $args = false)
	{
		if ($args === false)
		{
			$args =& $_GET;

			self::$get = (object) array_map(array('CIA', 'htmlescape'), $_GET);
			self::$get->__SCRIPT__ = CIA::htmlescape($_SERVER['SCRIPT_NAME']);
			self::$get->__URI__ = CIA::htmlescape($_SERVER['REQUEST_URI']);
			self::$get->__ROOT__ = CIA::htmlescape(CIA_ROOT);
			self::$get->__LANG__ = CIA::htmlescape(CIA_LANG);
			self::$get->__AGENT__ = CIA::htmlescape($agent) . ('' !== $agent ? '/' : '');
			self::$get->__HOST__ = CIA::htmlescape('http' . (@$_SERVER['HTTPS']?'s':'') . '://' . @$_SERVER['HTTP_HOST']);

			if (!CIA_BINARY) CIA::setCacheControl(-1, true, false);
		}

		$a =& $_GET;
		$_GET =& $args;

		self::render($agent);

		$_GET =& $a;
	}

	public static function render($agent)
	{
		CIA::$headersDiff = array();

		$agentClass = CIA::agentClass($agent);
		$agent = class_exists($agentClass) ? new $agentClass($_GET) : new agentTemplate_(array('template' => $agent));

		$cagent = CIA::agentCache($agentClass, $agent->argv);

		if (!(CIA_POSTING && $agent->canPost) && isset(self::$cache[$cagent]))
		{	
			$cagent =& self::$cache[$cagent];
			$v = clone $cagent[0];
			$template = $cagent[1];
		}
		else
		{
			if (!(CIA_POSTING && $agent->canPost) && file_exists($cagent) && filemtime($cagent)>CIA_TIME) require $cagent;
			else
			{
				CIA::$privateTrigger = false;
				$v = $agent->render();
				list($maxage, $expires, $private, $template, $watch) = $agent->getMeta();
				$expires = !('ontouch' == $expires && $watch);

				if (CIA::$privateTrigger) $private = true;
				CIA::setCacheControl($maxage, $private, $expires);
	
				if (!$private && ($maxage || !$expires))
				{
					$cagent = CIA::agentCache($agentClass, $agent->argv);

					CIA::makeDir($cagent);

					$h = fopen($cagent, 'wb');

					fwrite($h, '<?php $v=(object)');
					self::writeAgent($h, $v);
					fwrite(
						$h,
						';$template=' . var_export($template, true)
							. ';CIA::setCacheControl(' . (int) $maxage . ', 0, ' . (int) $expires . ');'
							. (CIA::$headersDiff ? "header('" . addslashes(implode("\n", CIA::$headersDiff)) . "');" : '')
					);

					fclose($h);
					touch($cagent, CIA_TIME + ($maxage>0 && $expires ? $maxage : CIA_MAXAGE));

					CIA::writeWatchTable($watch, $cagent);
				}
			}

			self::$cache[$cagent] = array(clone $v, $template);
		}

		$a = self::$args = (object) $_GET;
		$v->{'$'} = $v;
		$g = self::$get;

		self::$values = $v;

		$ctemplate = './tmp/cache/' . CIA_LANG . "/templates/$template.php";
		$ftemplate = 'template' . md5($ctemplate);

		if (function_exists($ftemplate)) $ftemplate($v, $a, $g);
		else
		{
			if (!file_exists($ctemplate))
			{
				$compiler = new iaCompiler_php;
				$template = '<?php function ' . $ftemplate . '(&$v, &$a, &$g){' . $compiler->compile($template . '.tpl') . '} ' . $ftemplate . '($v, $a, $g);';
				CIA::writeFile($ctemplate,  $template);
				CIA::writeWatchTable(array('public/templates'), $ctemplate);
			}

			require $ctemplate;
		}
	}

	private static function writeAgent(&$h, &$data)
	{
		fwrite($h, 'array(');

		$comma = '';
		foreach ($data as $key => $value)
		{
			fwrite($h, $comma . "'" . str_replace(array('\\',"'"), array('\\\\',"\\'"), $key) . "'=>");
			if ($value instanceof loop)
			{
				if (!CIA::string($value)) fwrite($h, "'0'");
				else
				{
					fwrite($h, 'new L_(array(');

					$comma2 = '';
					while ($key = $value->render())
					{
						fwrite($h, $comma2);
						self::writeAgent($h, $key);
						$comma2 = ',';
					}

					fwrite($h, '))');
				}

			}
			else fwrite($h, "'" . str_replace(array('\\',"'"), array('\\\\',"\\'"), $value) . "'");
			$comma = ',';
		}

		fwrite($h, ')');
	}

	/*
	* Used internaly at template execution time, for counters.
	*/
	public static function increment($var, $step, $pool)
	{
		if (!isset($pool->$var)) $pool->$var = 0;

		$var =& $pool->$var;

		if (!$var) $var = '0';
		$a = $var;
		$var += $step;
		return $a;
	}

	public static function escape(&$object)
	{
		if (!CIA_BINARY) foreach ($object as $k => $v) if (is_string($v)) $object->$k = htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
	}
}

class L_ extends loop
{
	protected $array;
	protected $len;
	protected $i = 0;

	public function __construct($array)
	{
		$this->array =& $array;
	}

	protected function prepare()
	{
		return $this->len = count($this->array);
	}

	protected function next()
	{
		if ($this->i < $this->len) return (object) $this->array[$this->i++];
		else $this->i = 0;
	}
}

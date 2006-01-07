<?php

class IA_php
{
	protected static $args;
	protected static $values;
	protected static $get;

	protected static $cache = array();

	public static function loadAgent($agent, $args = array())
	{
		if (!isset(self::$args))
		{
			if (false===$args) $args =& $_GET;

			self::$get = (object) array_map('htmlspecialchars', $_GET);
			self::$get->__QUERY__ = '?' . htmlspecialchars($_SERVER['QUERY_STRING']);
			self::$get->__URI__ = htmlspecialchars($_SERVER['REQUEST_URI']);
			self::$get->__ROOT__ = htmlspecialchars(CIA_ROOT);
			self::$get->__LANG__ = htmlspecialchars(CIA_LANG);
			self::$get->__AGENT__ = htmlspecialchars($agent) . ('' !== $agent ? '/' : '');
			self::$get->__HOST__ = htmlspecialchars('http' . (@$_SERVER['HTTPS']?'s':'') . '://' . @$_SERVER['HTTP_HOST']);
		}

		if ($agent instanceof loop && CIA::string($agent))
		{
			while ($i =& $agent->render()) $data =& $i;

			$agent = $data->{'*a'};

			IA::escape($data);
			foreach ($data as $k => $v) $args[$k] = $v;
		}

		$a =& $_GET;
		$_GET =& $args;

		self::render($agent);

		$_GET =& $a;
	}

	public static function render($agent)
	{
		CIA::openMeta();

		$agentClass = CIA::agentClass($agent);
		$agent = class_exists($agentClass) ? new $agentClass($_GET) : new agentTemplate_(array('template' => $agent));

		$cagent = CIA::agentCache($agentClass, $agent->argv, 'php');
		$rendered = false;

		if (isset(self::$cache[$cagent]))
		{
			$cagent =& self::$cache[$cagent];
			$v = clone $cagent[0];
			$template = $cagent[1];
		}
		else
		{
			if (file_exists($cagent) && filemtime($cagent)>CIA_TIME) require $cagent;
			else if (!CIA_POSTING && file_exists('POST'.$cagent) && filemtime('POST'.$cagent)>CIA_TIME) require 'POST'.$cagent;
			else
			{
				$v = $agent->render();
				$template = $agent->getTemplate();
				$rendered = true;
			}

			$vClone = clone $v;
		}

		CIA::$catchMeta = false;

		$a = self::$args = (object) $_GET;
		$v->{'$'} = $v;
		$g = self::$get;

		self::$values = $v;

		$ctemplate = './tmp/cache/' . CIA_LANG . "/templates/$template" . ($agent->binary ? '.bin' : '.html') . '.php';
		$ftemplate = 'template' . md5($ctemplate);

		if (function_exists($ftemplate)) $ftemplate($v, $a, $g);
		else
		{
			if (!file_exists($ctemplate))
			{
				$compiler = new iaCompiler_php($agent->binary);
				$ftemplate = '<?php function ' . $ftemplate . '(&$v, &$a, &$g){' . $compiler->compile($template . '.tpl') . '} ' . $ftemplate . '($v, $a, $g);';
				CIA::writeFile($ctemplate,  $ftemplate);
				CIA::writeWatchTable(array('public/templates'), $ctemplate);
			}

			require $ctemplate;
		}

		CIA::$catchMeta = true;

		$agent->postRender();
		list($maxage, $private, $expires, $watch, $headers, $canPost) = CIA::closeMeta();

		if ($rendered)
		{
			$cagent = CIA::agentCache($agentClass, $agent->argv, 'php');

			if (!CIA_POSTING && !$private && ($maxage || ('ontouch' == $expires && $watch)))
			{
				$fagent = $cagent . ($canPost ? '.php' : '');

				CIA::makeDir($fagent);

				$h = fopen($fagent, 'wb');

				fwrite($h, '<?php $v=(object)');
				self::writeAgent($h, $vClone);
				fwrite(
					$h,
					';$template=' . var_export($template, true)
						. ';CIA::setMaxage(' . (int) $maxage . ');'
						. ('ontouch' != $expires ? 'CIA::setExpires("onmaxage");' : '')
						. ($headers ? "header('" . addslashes(implode("\n", $headers)) . "');" : '')
				);

				fclose($h);
				touch($fagent, CIA_TIME + ('ontouch' == $expires && $watch ? CIA_MAXAGE : $maxage));

				CIA::writeWatchTable($watch, $fagent);
			}
		}

		if (isset($vClone)) self::$cache[$cagent] = array($vClone, $template);
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
		foreach ($object as $k => $v) if (is_string($v)) $object->$k = htmlspecialchars($v);
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

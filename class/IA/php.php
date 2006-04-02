<?php

class IA_php
{
	protected static $args;
	protected static $values;
	protected static $get = false;

	protected static $masterCache = array();
	protected static $cache;

	public static function loadAgent($agent, $args = array())
	{
		$a =& $_GET;

		if (!self::$get)
		{
			$reset_get = true;
			$cache = '';

			self::$get = (object) array_map('htmlspecialchars', $a);
			self::$get->__DEBUG__ = DEBUG ? 1 : 0;
			self::$get->__QUERY__ = '?' . htmlspecialchars($_SERVER['QUERY_STRING']);
			$cache .= self::$get->__ROOT__ = htmlspecialchars(CIA::__ROOT__());
			$cache .= self::$get->__LANG__ = htmlspecialchars(CIA::__LANG__());
			self::$get->__AGENT__ = htmlspecialchars($agent) . ('' !== $agent ? '/' : '');
			$cache .= self::$get->__HOST__ = htmlspecialchars(CIA::__HOST__());
			self::$get->__URI__ = htmlspecialchars($_SERVER['REQUEST_URI']);

			if (!isset(self::$masterCache[$cache])) self::$masterCache[$cache] = array();

			self::$cache =& self::$masterCache[$cache];
		}
		else $reset_get = false;

		if (false===$args) $args = self::$get;
		else $_GET =& $args;

		if ($agent instanceof loop && CIA::string($agent))
		{
			while ($i =& $agent->compose()) $data =& $i;

			$agent = $data->{'*a'};

			self::escape($data);
			foreach ($data as $k => $v) $args[$k] = $v;
		}

		self::compose($agent);

		$_GET =& $a;

		if ($reset_get) self::$get = false;
	}

	public static function compose($agent)
	{
		CIA::openMeta();

		$a = self::$args = (object) $_GET;
		$g = self::$get;

		$agentClass = CIA::agentClass($agent);
		$agent = class_exists($agentClass) ? new $agentClass($_GET) : new agentTemplate_(array('template' => $agent));

		$cagent = CIA::agentCache($agentClass, $agent->argv, 'php');
		$filter = false;

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
				$v = $agent->compose();
				$template = $agent->getTemplate();
				$filter = true;
			}

			$vClone = clone $v;
		}

		CIA::$catchMeta = false;

		self::$values = $v->{'$'} = $v;

		$ctemplate = CIA::makeCacheDir('templates/' . $template . ($agent->binary ? '.bin' : '.html') . '.php');
		$ftemplate = 'template' . md5($ctemplate);

		if (function_exists($ftemplate)) $ftemplate($v, $a, $g);
		else
		{
			if (!file_exists($ctemplate))
			{
				$compiler = new iaCompiler_php($agent->binary);
				$ftemplate = '<?php function ' . $ftemplate . '(&$v, &$a, &$g){$d=$v;' . $compiler->compile($template . '.tpl') . '} ' . $ftemplate . '($v, $a, $g);';
				CIA::writeFile($ctemplate,  $ftemplate);
				CIA::writeWatchTable('public/templates', $ctemplate);
			}

			require $ctemplate;
		}

		CIA::$catchMeta = true;

		$agent->metaCompose();
		list($maxage, $private, $expires, $watch, $headers, $canPost) = CIA::closeMeta();

		if ($filter)
		{
			$cagent = CIA::agentCache($agentClass, $agent->argv, 'php');

			if (!CIA_POSTING && !$private && ($maxage || ('ontouch' == $expires && $watch)))
			{
				$fagent = $cagent . ($canPost ? '.php' : '');

				CIA::makeDir($fagent);

				$h = fopen($fagent, 'wb');

				fwrite($h, '<?php $v=(object)');

				self::writeAgent($h, $vClone);

				$data = ';$template=' . var_export($template, true)
					. ';CIA::setMaxage(' . (int) $maxage . ');'
					. ('ontouch' != $expires ? 'CIA::setExpires("onmaxage");' : '');

				if ($headers)
				{
					$headers = array_map('addslashes', $headers);
					$data .= "header('" . implode("');header('", $headers) . "');";
				}

				fwrite($h, $data);
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
					while ($key = $value->compose())
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

	public static function makeLoopByLength(&$length)
	{
		$length = new loop_length_($length);
		return true;
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

class loop_length_ extends loop
{
	protected $length;
	protected $counter;

	public function __construct($length)
	{
		$this->length = $length;
	}

	protected function prepare()
	{
		$this->counter = 0;
		return $this->length;
	}

	protected function next()
	{
		if ($this->counter++ < $this->length) return (object) array();
	}
}

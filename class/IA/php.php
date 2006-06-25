<?php

class IA_php
{
	protected static $args;
	protected static $values;
	protected static $get;

	protected static $masterCache = array();
	protected static $cache;

	public static function returnAgent($agent, $args, $lang = false)
	{
		if ($lang) $lang = CIA::setLang($lang);

		$a =& $_GET;
		$g =& self::$get;

		$_GET =& $args;
		self::$get =& $f;

		ob_start();
		IA_php::loadAgent(CIA::resolveAgentClass($agent, $_GET), false, false);
		$agent = ob_get_clean();

		$_GET =& $a;
		self::$get =& $g;

		if ($lang) CIA::setLang($lang);

		return $agent;
	}

	public static function loadAgent($agent, $args, $is_exo)
	{
		$a =& $_GET;

		if (false === $args)
		{
			$reset_get = true;
			$cache = '';

			if (isset($_GET['$s']))
			{
				ob_start('htmlspecialchars');
				self::$get = (object) $a;
			}
			else self::$get = (object) array_map('htmlspecialchars', $a);

			self::$get->__DEBUG__ = DEBUG ? DEBUG : 0;
			self::$get->__HOST__ = CIA::__HOST__();
			$cache .= self::$get->__LANG__ = CIA::__LANG__();
			$cache .= self::$get->__HOME__ = CIA::__HOME__();
			self::$get->__AGENT__ = 'agent_index' == $agent ? '' : (str_replace('_', '/', substr($agent, 6)) . '/');
			self::$get->__URI__ = htmlspecialchars(CIA::__URI__());

			self::$args = self::$get;

			if (!isset(self::$masterCache[$cache])) self::$masterCache[$cache] = array();

			self::$cache =& self::$masterCache[$cache];
		}
		else
		{
			$reset_get = false;
			$_GET =& $args;

			if ($agent instanceof loop && CIA::string($agent))
			{
				$agent->autoResolve = false;

				while ($i =& $agent->compose()) $data =& $i;

				$agent = $data->{'a$'};

				foreach ($data as $k => $v) $args[$k] = is_string($v) ? htmlspecialchars($v) : $v;
			}

			$HOME = CIA::__HOME__();
			$agent = CIA::home($agent);

			if (0 === strpos($agent, $HOME))
			{
				$agent = substr($agent, strlen($HOME));

				if ($is_exo)
				{
					E("CIA Security Restriction Error: an AGENT ({$agent}) is called with EXOAGENT");
					$_GET =& $a;
					return;
				}
			}
			else
			{
				if ($is_exo)
				{
					require_once 'HTTP/Request.php';
					$agent = preg_replace("'__'", CIA::__LANG__(), $agent, 1);
					$agent = new HTTP_Request($agent);
					$agent->addQueryString('$s', '');
					foreach ($args as $k => $v) $agent->addQueryString($k, CIA::string($v));

					$agent->sendRequest();

					echo str_replace(
						array('&gt;', '&lt;', '&quot;', '&#039;', '&amp;'),
						array('>'   , '<'   , '"'     , "'"     , '&'    ),
						$agent->getResponseBody()
					);
				}
				else E("CIA Security Restriction Error: an EXOAGENT ({$agent}) is called with AGENT");

				$_GET =& $a;
				return;
			}

			$agent = CIA::resolveAgentClass($agent, $args);
			self::$args = (object) $args;
		}

		self::compose($agent);

		$_GET =& $a;

		if ($reset_get) self::$get = false;
	}

	protected static function compose($agentClass)
	{
		CIA::openMeta($agentClass);

		$a = self::$args;
		$g = self::$get;

		$agent = new $agentClass($_GET);

		$group = CIA::closeGroupStage();

		$is_cacheable = !in_array('private', $group);

		$cagent = CIA::agentCache($agentClass, $agent->argv, 'php', $group);

		$filter = false;

		if (isset(self::$cache[$cagent]))
		{
			$cagent =& self::$cache[$cagent];
			$v = clone $cagent[0];
			$template = $cagent[1];
		}
		else
		{
			if ($is_cacheable && file_exists($cagent) && filemtime($cagent)>CIA_TIME) require $cagent;
			else
			{
				$v = substr($cagent, 0, -7) . 'post' . substr($cagent, -4);

				if ($is_cacheable && !CIA_POSTING && file_exists($v) && filemtime($v)>CIA_TIME) require $v;
				else
				{
					ob_start();
					$v = (object) $agent->compose();
					$template = $agent->getTemplate();
					$filter = true;
					$rawdata = ob_get_flush();
				}
			}

			$vClone = clone $v;
		}

		CIA::$catchMeta = false;

		self::$values = $v->{'$'} = $v;

		$ctemplate = CIA::makeCacheDir('templates/' . $template, (constant("$agentClass::binary") ? 'bin' : 'html') . '.php');
		$ftemplate = 'template' . md5($ctemplate);

		if (function_exists($ftemplate)) $ftemplate($v, $a, $g);
		else
		{
			if (!file_exists($ctemplate))
			{
				CIA::openMeta('agent__template/' . $template, false);
				$compiler = new iaCompiler_php(constant("$agentClass::binary"));
				$ftemplate = '<?php function ' . $ftemplate . '(&$v, &$a, &$g){$d=$v;' . $compiler->compile($template . '.tpl') . '} ' . $ftemplate . '($v, $a, $g);';
				CIA::writeFile($ctemplate,  $ftemplate);
				list(,,, $watch) = CIA::closeMeta();
				CIA::writeWatchTable($watch, $ctemplate);
			}

			require $ctemplate;
		}

		if ($filter)
		{
			CIA::$catchMeta = true;
			$agent->metaCompose();
			list($maxage, $group, $expires, $watch, $headers, $canPost) = CIA::closeMeta();

			if ('ontouch' == $expires && !$watch) $expires = 'auto';
			$expires = 'auto' == $expires && $watch ? 'ontouch' : 'onmaxage';

			if ($is_cacheable && !CIA_POSTING && !in_array('private', $group) && ($maxage || 'ontouch' == $expires))
			{
				$fagent = $cagent;
				if ($canPost) $fagent = substr($cagent, 0, -7) . 'post' . substr($cagent, -4);

				CIA::makeDir($fagent);

				$tmpname = dirname($fagent) . '/' . CIA::uniqid();

				$h = fopen($tmpname, 'wb');

				$rawdata = str_replace('<?', "<?php echo'<?'?>", $rawdata) . '<?php $v=(object)';
				fwrite($h, $rawdata, strlen($rawdata));

				self::writeAgent($h, $vClone);

				$data = ';$template=' . var_export($template, true)
					. ';CIA::setMaxage(' . (int) $maxage
					. ");CIA::setExpires('$expires');";

				if ($headers)
				{
					$headers = array_map('addslashes', $headers);
					$data .= "header('" . implode("');header('", $headers) . "');";
				}

				fwrite($h, $data, strlen($data));
				fclose($h);

				if ('WIN' == substr(PHP_OS, 0, 3)) @unlink($fagent);
				@rename($tmpname, $fagent);

				touch($fagent, CIA_TIME + ('ontouch' == $expires ? CIA_MAXAGE : $maxage));

				CIA::writeWatchTable($watch, $fagent);
			}
		}
		else CIA::closeMeta();

		if (isset($vClone)) self::$cache[$cagent] = array($vClone, $template);
	}

	private static function writeAgent(&$h, &$data)
	{
		fwrite($h, 'array(', 6);

		$comma = '';
		foreach ($data as $key => $value)
		{
			$comma .= "'" . str_replace(array('\\',"'"), array('\\\\',"\\'"), $key) . "'=>";
			fwrite($h, $comma, strlen($comma));

			if ($value instanceof loop)
			{
				if (!CIA::string($value)) fwrite($h, "'0'", 3);
				else
				{
					fwrite($h, 'new L_(array(', 13);

					$comma2 = '';
					while ($key = $value->compose())
					{
						fwrite($h, $comma2, strlen($comma2));
						self::writeAgent($h, $key);
						$comma2 = ',';
					}

					fwrite($h, '))', 2);
				}

			}
			else
			{
				$comma = "'" . str_replace(array('\\',"'"), array('\\\\',"\\'"), $value) . "'";
				fwrite($h, $comma, strlen($comma));
			}

			$comma = ',';
		}

		fwrite($h, ')', 1);
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

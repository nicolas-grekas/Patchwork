<?php /*********************************************************************
 *
 *   Copyright : (C) 2006 Nicolas Grekas. All rights reserved.
 *   Email     : nicolas.grekas+patchwork@espci.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL, see COPYING
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


class extends CIA
{
	protected static $args;
	protected static $values;
	protected static $get;

	protected static $masterCache = array();
	protected static $cache;

	static function returnAgent($agent, $args, $lang = false)
	{
		if ($lang) $lang = CIA::setLang($lang);

		$a =& $_GET;
		$g =& self::$get;

		$_GET =& $args;
		self::$get =& $f;

		ob_start();
		self::loadAgent(CIA::resolveAgentClass($agent, $_GET), false, false);
		$agent = ob_get_clean();

		$_GET =& $a;
		self::$get =& $g;

		if ($lang) CIA::setLang($lang);

		return $agent;
	}

	static function loadAgent($agent, $args, $is_exo)
	{
		if (null === $agent) return;

		$a =& $_GET;

		if (false === $args)
		{
			$reset_get = true;
			$cache = '';

			if (isset($_GET['s$']))
			{
				ob_start(array(__CLASS__, 'ob_htmlspecialchars'), 8192);
				++CIA::$ob_level;
				self::$get = (object) $a;
			}
			else self::$get = (object) array_map('htmlspecialchars', $a);

			CIA::$uri = CIA::$host . substr($_SERVER['REQUEST_URI'], 1);

			self::$get->__DEBUG__ = DEBUG ? DEBUG : 0;
			self::$get->__HOST__ = htmlspecialchars(CIA::__HOST__());
			$cache .= self::$get->__LANG__ = htmlspecialchars(CIA::__LANG__());
			$cache .= self::$get->__BASE__ = htmlspecialchars(CIA::__BASE__());
			self::$get->__AGENT__ = 'agent_index' == $agent ? '' : (str_replace('_', '/', substr($agent, 6)) . '/');
			self::$get->__URI__ = htmlspecialchars(CIA::$uri);
			self::$get->__REFERER__ = isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : '';

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

				while ($i =& $agent->loop()) $data =& $i;

				$agent = $data->{'a$'};

				foreach ($data as $k => &$v) $args[$k] = is_string($v) ? htmlspecialchars($v) : $v;
			}

			$BASE = CIA::__BASE__();
			$agent = CIA::base($agent, true);

			if (0 === strpos($agent, $BASE))
			{
				$agent = substr($agent, strlen($BASE));

				if ($is_exo)
				{
					W("CIA Security Restriction Error: an AGENT ({$agent}) is called with EXOAGENT");
					$_GET =& $a;
					return;
				}
			}
			else
			{
				if ($is_exo)
				{
					$agent = implode(CIA::__LANG__(), explode('__', $agent, 2)) . '?s$';

					foreach ($args as $k => &$v) $agent .= '&' . urlencode($k) . '=' . urlencode(CIA::string($v));

					if (ini_get('allow_url_fopen')) $agent = file_get_contents($agent);
					else
					{
						$agent = new HTTP_Request($agent);
						$agent->sendRequest();
						$agent = $agent->getResponseBody();
					}

					echo str_replace(
						array('&gt;', '&lt;', '&quot;', '&#039;', '&amp;'),
						array('>'   , '<'   , '"'     , "'"     , '&'    ),
						$agent
					);
				}
				else W("CIA Security Restriction Error: an EXOAGENT ({$agent}) is called with AGENT");

				$_GET =& $a;
				return;
			}

			$agent = CIA::resolveAgentClass($agent, $args);
			self::$args = (object) $args;
		}

		self::render($agent);

		$_GET =& $a;

		if ($reset_get) self::$get = false;
	}

	protected static function render($agentClass)
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
			if (!($is_cacheable && list($v, $template) = self::getFromCache($cagent)))
			{
				ob_start();
				++CIA::$ob_level;

				$v = (object) $agent->compose((object) array());

				if (!CIA::$is_enabled)
				{
					CIA::closeMeta();
					return;
				}

				$template = $agent->getTemplate();

				$filter = true;

				$rawdata = ob_get_flush();
				--CIA::$ob_level;
			}

			$vClone = clone $v;
		}

		CIA::$catchMeta = false;

		self::$values = $v->{'$'} = $v;

		$ctemplate = CIA::getContextualCachePath('templates/' . $template, (constant("$agentClass::binary") ? 'bin' : 'html') . '.php');
		$ftemplate = 'template' . md5($ctemplate);

		if (function_exists($ftemplate)) $ftemplate($v, $a, $g);
		else
		{
			$readHandle = true;

			if ($h = CIA::fopenX($ctemplate, $readHandle))
			{
				CIA::openMeta('agent__template/' . $template, false);
				$compiler = new iaCompiler_php(constant("$agentClass::binary"));
				$ftemplate = '<?php function ' . $ftemplate . '(&$v, &$a, &$g){global $a' . $GLOBALS['cia_paths_token'] . ',$c' . $GLOBALS['cia_paths_token'] . ';$d=$v;' . $compiler->compile($template . '.tpl') . '} ' . $ftemplate . '($v, $a, $g);';
				fwrite($h, $ftemplate, strlen($ftemplate));
				fclose($h);
				list(,,, $watch) = CIA::closeMeta();
				CIA::writeWatchTable($watch, $ctemplate);
			}
			else fclose($readHandle);

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

				if ($h = CIA::fopenX($fagent))
				{
					if (false !== strpos($rawdata, '<?')) $rawdata = str_replace('<?', '<<?php ?>?', $rawdata);
					$rawdata .= '<?php $v=(object)';
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

					touch($fagent, $_SERVER['REQUEST_TIME'] + ('ontouch' == $expires ? CIA_MAXAGE : $maxage));

					CIA::writeWatchTable($watch, $fagent);
				}
			}
		}
		else CIA::closeMeta();

		if (isset($vClone)) self::$cache[$cagent] = array($vClone, $template);
	}

	protected static function writeAgent(&$h, &$data)
	{
		fwrite($h, 'array(', 6);

		$comma = '';
		foreach ($data as $key => &$value)
		{
			$comma .= "'" . str_replace(array('\\', "'"), array('\\\\', "\\'"), $key) . "'=>";
			fwrite($h, $comma, strlen($comma));

			if ($value instanceof loop)
			{
				if (!CIA::string($value)) fwrite($h, "'0'", 3);
				else
				{
					fwrite($h, 'new L_(array(', 13);

					$comma2 = '';
					while ($key = $value->loop())
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
				$comma = "'" . str_replace(array('\\', "'"), array('\\\\', "\\'"), $value) . "'";
				fwrite($h, $comma, strlen($comma));
			}

			$comma = ',';
		}

		fwrite($h, ')', 1);
	}

	protected static function getFromCache($cagent)
	{
		if (file_exists($cagent))
		{
			if (filemtime($cagent)>$_SERVER['REQUEST_TIME'])
			{
				require $cagent;
				return array($v, $template);
			}
			else unlink($cagent);

			return false;
		}

		$cagent = substr($cagent, 0, -7) . 'post' . substr($cagent, -4);

		if (!CIA_POSTING && file_exists($cagent))
		{
			if (filemtime($cagent)>$_SERVER['REQUEST_TIME'])
			{
				require $cagent;
				return array($v, $template);
			}
			else unlink($cagent);
		}

		return false;
	}

	/*
	* Used internaly at template execution time, for counters.
	*/
	static function increment($var, $step, $pool)
	{
		if (!isset($pool->$var)) $pool->$var = 0;

		$var =& $pool->$var;

		if (!$var) $var = '0';
		$a = $var;
		$var += $step;
		return $a;
	}

	static function escape(&$object)
	{
		foreach ($object as &$v) if (is_string($v)) $v = htmlspecialchars($v);
	}

	static function makeLoopByLength(&$length)
	{
		$length = new loop_length_($length);
		return true;
	}

	static function ob_htmlspecialchars($a, $mode)
	{
		if (PHP_OUTPUT_HANDLER_END & $mode) --CIA::$ob_level;

		return htmlspecialchars($a);
	}
}

class L_ extends loop
{
	protected $array;
	protected $len;
	protected $i = 0;

	function __construct($array)
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

	function __construct($length)
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

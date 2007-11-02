<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 3 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


class extends patchwork
{
	protected static

	$args,
	$values,
	$get,

	$masterCache = array(),
	$cache;


	static function returnAgent($agent, $args, $lang = false)
	{
		if (false !== $lang)
		{
			$a = p::$lang;
			p::setLang($lang);
			$lang = $a;
		}

		$a =& $_GET;
		$g =& self::$get;

		$_GET =& $args;
		self::$get =& $f;

		ob_start();
		self::loadAgent(p::resolveAgentClass($agent, $_GET), false, false);
		$agent = ob_get_clean();

		$_GET =& $a;
		self::$get =& $g;

		false !== $lang && p::setLang($lang);

		return $agent;
	}

	static function loadAgent($agent, $args, $is_exo)
	{
		if (null === $agent) return;

		$a =& $_GET;

		if (false === $args)
		{
#>			p::$binaryMode || p::touch('debugSync');

			$reset_get = true;
			$cache = '';

			if (isset($_GET['s$']))
			{
				ob_start(array(__CLASS__, 'ob_htmlspecialchars'), 8192);
				++p::$ob_level;
				self::$get = (object) $a;
			}
			else self::$get = (object) array_map('htmlspecialchars', $a);

			self::$get->__DEBUG__ = DEBUG ? DEBUG : 0;
			self::$get->__HOST__ = htmlspecialchars(p::__HOST__());
			$cache .= self::$get->__LANG__ = htmlspecialchars(p::__LANG__());
			$cache .= self::$get->__BASE__ = htmlspecialchars(p::__BASE__());
			self::$get->__AGENT__ = 'agent_index' === $agent ? '' : (str_replace('_', '/', substr($agent, 6)) . '/');
			self::$get->__URI__ = htmlspecialchars(p::$uri);
			self::$get->__REFERER__ = isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : '';
			self::$get->__LANG_ALT__ = new loop_altLang;

			self::$args = self::$get;

			if (!isset(self::$masterCache[$cache])) self::$masterCache[$cache] = array();

			self::$cache =& self::$masterCache[$cache];
		}
		else
		{
			$reset_get = false;
			$_GET =& $args;

			if ($agent instanceof loop && p::string($agent))
			{
				$agent->autoResolve = false;

				while ($i =& $agent->loop()) $data =& $i;

				if (!(p::$binaryMode || $agent instanceof L_)) foreach ($data as &$v) is_string($v) && $v = htmlspecialchars($v);

				$agent = $data->{'a$'};
				$args = array_merge((array) $data, $args);
			}

			$BASE = p::__BASE__();
			$agent = p::base($agent, true);

			if (0 === strpos($agent, $BASE))
			{
				$agent = substr($agent, strlen($BASE));

				if ($is_exo)
				{
					W("patchwork Security Restriction Error: an AGENT ({$agent}) is called with EXOAGENT");
					$_GET =& $a;
					return;
				}
			}
			else
			{
				if ($is_exo)
				{
					$k = $CONFIG['i18n.lang_list'][p::__LANG__()];
					$agent = implode($k, explode('__', $agent, 2)) . '?s$';

					foreach ($args as $k => &$v) $agent .= '&' . urlencode($k) . '=' . urlencode(p::string($v));

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
				else W("patchwork Security Restriction Error: an EXOAGENT ({$agent}) is called with AGENT");

				$_GET =& $a;
				return;
			}

			$agent = p::resolveAgentClass($agent, $args);
			self::$args = (object) $args;
		}

		self::render($agent);

		$_GET =& $a;

		if ($reset_get) self::$get = false;
	}

	protected static function render($agentClass)
	{
		p::openMeta($agentClass);

		$a = self::$args;
		$g = self::$get;

		$agent = new $agentClass($_GET);

		$group = p::closeGroupStage();

		$is_cacheable = !in_array('private', $group);

		$cagent = p::agentCache($agentClass, $agent->get, 'ser', $group);

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
				++p::$ob_level;

				$v = (object) $agent->compose((object) array());

				if (!p::$is_enabled)
				{
					p::closeMeta();
					return;
				}

				$template = $agent->getTemplate();

				if (!p::$binaryMode)
				{
					foreach ($v as &$h) is_string($h) && $h = htmlspecialchars($h);
					unset($h);
				}

				$filter = true;

				$rawdata = ob_get_flush();
				--p::$ob_level;
			}

			isset(p::$headers['content-type']) || p::header('Content-Type: text/html');

			$vClone = clone $v;
		}

		p::$catchMeta = false;

		self::$values = $v->{'$'} = $v;

		$ctemplate = p::getContextualCachePath('templates/' . $template, (p::$binaryMode ? 'bin' : 'html') . '.php');
		$ftemplate = 'template' . md5($ctemplate);

		if (function_exists($ftemplate)) $ftemplate($v, $a, $g);
		else
		{
			TURBO || p::syncTemplate($template, $ctemplate);

			if ($h = p::fopenX($ctemplate))
			{
				p::openMeta('agent__template/' . $template, false);
				$compiler = new ptlCompiler_php(p::$binaryMode);
				$ftemplate = '<?php function ' . $ftemplate . '(&$v, &$a, &$g){global $a' . PATCHWORK_PATH_TOKEN . ',$c' . PATCHWORK_PATH_TOKEN . ';$d=$v;' . $compiler->compile($template . '.ptl') . '} ' . $ftemplate . '($v, $a, $g);';
				fwrite($h, $ftemplate);
				fclose($h);
				list(,,, $watch) = p::closeMeta();
				p::writeWatchTable($watch, $ctemplate);
			}

			require $ctemplate;
		}

		if ($filter)
		{
			p::$catchMeta = true;
			$agent->metaCompose();
			list($maxage, $group, $expires, $watch, $headers, $canPost) = p::closeMeta();

			if ('ontouch' === $expires && !$watch) $expires = 'auto';
			$expires = 'auto' === $expires && $watch ? 'ontouch' : 'onmaxage';

			p::setExpires($expires);

			if ($is_cacheable && !IS_POSTING && !in_array('private', $group) && ($maxage || 'ontouch' === $expires))
			{
				$fagent = $cagent;
				if ($canPost) $fagent = substr($cagent, 0, -4) . '.post' . substr($cagent, -4);

				if ($h = p::fopenX($fagent))
				{
					$rawdata = array(
						'rawdata' => $rawdata,
						'v' => array(),
					);

					self::freezeAgent($rawdata['v'], $vClone);

					$rawdata['template'] = $template;
					$rawdata['maxage']   = $maxage;
					$rawdata['expires']  = $expires;
					$rawdata['watch']    = $watch;
					$rawdata['headers']  = $headers;

					$rawdata = serialize($rawdata);

					fwrite($h, $rawdata);
					fclose($h);

					touch($fagent, $_SERVER['REQUEST_TIME'] + ('ontouch' === $expires ? $CONFIG['maxage'] : $maxage));

					p::writeWatchTable($watch, $fagent);
				}
			}
		}
		else p::closeMeta();

		if (isset($vClone)) self::$cache[$cagent] = array($vClone, $template);
	}

	protected static function freezeAgent(&$v, &$data)
	{
		foreach ($data as $key => &$value)
		{
			if ($value instanceof loop)
			{
				if (p::string($value))
				{
					$a = array();

					while ($b = $value->loop(!p::$binaryMode))
					{
						$c = array();
						$a[] =& $c;
						self::freezeAgent($c, $b);
						unset($c);
					}

					$v[$key] = new L_($a);
					unset($a);
				}
			}
			else $v[$key] =& $value;
		}
	}

	protected static function getFromCache($cagent)
	{
		if (!file_exists($cagent))
		{
			$cagent = substr($cagent, 0, -4) . '.post' . substr($cagent, -4);
			if (IS_POSTING || !file_exists($cagent)) $cagent = false;
		}

		if ($cagent)
		{
			if (filemtime($cagent) > $_SERVER['REQUEST_TIME'])
			{
				$data = unserialize(file_get_contents($cagent));
				p::setMaxage($data['maxage']);
				p::setExpires($data['expires']);
				p::writeWatchTable($data['watch']);
				array_map(array('patchwork', 'header'), $data['headers']);

				echo $data['rawdata'];

				return array((object) $data['v'], $data['template']);
			}
			else @unlink($cagent);
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

	static function makeLoopByLength(&$length)
	{
		$length = new loop_length_($length);
		return true;
	}

	static function ob_htmlspecialchars($a, $mode)
	{
		if (PHP_OUTPUT_HANDLER_END & $mode) --p::$ob_level;

		return htmlspecialchars($a);
	}
}

class L_ extends loop
{
	protected

	$array,
	$len,
	$i = 0;


	function __construct(&$array)
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
	protected

	$length,
	$counter;


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

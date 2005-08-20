<?php

class IA_php
{
	protected static $args;
	protected static $values;
	protected static $get;

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
		$agentClass = CIA::agentClass($agent);
		$agent = class_exists($agentClass) ? new $agentClass($_GET) : new agentTemplate_(array('template' => $agent));

		/* Get agent's data */
		$v = $agent->render();

		/* Initialize template's variables */
		$a = self::$args = (object) $_GET;
		$v->{'$'} = $v;
		$g = self::$get;

		self::$values = $v;

		$template = $agent->getTemplate();
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
				CIA::watch(array('public/templates'), $ctemplate);
			}

			require $ctemplate;
		}
	}

	/**
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

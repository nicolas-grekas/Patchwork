<?php

class
{
	static $pool = array();

	static function php($name)
	{
		$name = CIA::string($name);
		$args = func_get_args();
		$key =& self::$pool[$name];

		if (is_int($key))
		{
			if (++$key >= count($args)) $key = 1;
		}
		else $key = 1;

		return CIA::string($args[$key]);
	}

	static function js()
	{
		?>/*<script>*/

P$cycle = function($name)
{
	$name = str($name);
	var $args = P$cycle.arguments,
		$pool = cyclePool;

	if (t($pool[$name]))
	{
		if (++$pool[$name] >= $args.length) $pool[$name] = 1;
	}
	else $pool[$name] = 1;

	return str($args[$pool[$name]]);
}

cyclePool = [];

<?php	}
}

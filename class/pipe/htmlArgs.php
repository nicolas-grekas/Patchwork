<?php // vim: set enc=utf-8 ai noet ts=4 sw=4 fdm=marker:

class
{
	static function php($pool)
	{
		if (!$pool) return '';

		$except = func_get_args();
		$except = array_slice($except, 1);

		$result = '';
		foreach ($pool as $k => &$v)
		{
			if ('_'!=substr($k, 0, 1) && 'iteratorPosition'!=$k && strpos($k, '$')===false && !in_array($k, $except))
			{
				$result .= $k . '="' . CIA::string($v) . '" ';
			}
		}

		return $result;
	}

	static function js()
	{
		?>/*<script>*/

P$htmlArgs = function($pool)
{
	if (!$pool) return '';
	var $result = '', $argv = P$htmlArgs.arguments, $i = $argv.length, $except = [];

	while (--$i) $except[$i] = $argv[$i];
	$except = new RegExp('^(|'+$except.join('|')+')$');

	for ($i in $pool) if ('_'!=$i.substr(0, 1) && 'iteratorPosition'!=$i && $i.indexOf('$')<0 && $i.search($except)) $result += $i + '="' + $pool[$i] + '" ';

	return $result;
}

<?php	}
}

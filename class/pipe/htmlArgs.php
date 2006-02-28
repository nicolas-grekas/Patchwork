<?php

class pipe_htmlArgs
{
	static function php($pool)
	{
		if (!$pool) return '';

		$except = func_get_args();
		$except = array_slice($except, 1);

		$result = '';
		foreach ($pool as $k => $v)
		{
			$pool = substr($k, 0, 1);
			if ('_'!=$pool && '*'!=$pool && 'iteratorPosition'!=$k && mb_strpos($k, '$')===false && !in_array($k, $except))
			{
				$result .= $k . '="' . CIA::string($v) . '" ';
			}
		}

		return $result;
	}

	static function js()
	{
		?>/*<script>*/

P$<?php echo substr(__CLASS__, 5)?> = function($pool)
{
	if (!$pool) return '';
	var $result = '', $i = arguments.length, $except = [];

	while (--$i) $except[$i] = arguments[$i];
	$except = new RegExp('^(|'+$except.join('|')+')$');

	for ($i in $pool) if ($i.substr(0,1)!='_' && $i.substr(0,1)!='*' && 'iteratorPosition'!=$i && $i.indexOf('$')<0 && $i.search($except)) $result += $i + '="' + $pool[$i] + '" ';
	return $result;
}

<?php	}
}

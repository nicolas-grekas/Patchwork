<?php

class patchwork_alias
{
	static function resolve($c)
	{
		if (is_string($c) && isset($c[0]))
		{
			if ('\\' === $c[0])
			{
				if (!isset($c[1]) || '\\' === $c[1]) return $c;
				$c = substr($c, 1);
			}

			if (function_exists('__patchwork_' . strtr($c, '\\', '_')))
			{
				return '__patchwork_' . strtr($c, '\\', '_');
			}
		}

		return $c;
	}

	static function scopedResolve($c, &$v)
	{
		$v = self::resolve($c);
		return '?';
	}
}

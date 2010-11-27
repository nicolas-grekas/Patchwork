<?php

class patchwork_alias
{
	static function resolve($c)
	{
		return is_string($c) && function_exists('__patchwork_' . $c) ? '__patchwork_' . $c : $c;
	}

	static function scopedResolve($c, &$v)
	{
		$v = self::resolve($c);
		return '?';
	}
}

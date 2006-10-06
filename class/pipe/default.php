<?php

class
{
	static function php($string, $default = '')
	{
		return '' != (string) $string ? $string : $default;
	}

	static function js()
	{
		?>/*<script>*/

P$default = function($string, $default)
{
	return $string>'' ? $string : $default;
}

<?php	}
}

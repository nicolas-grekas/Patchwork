<?php

class
{
	static function php($string, $num)
	{
		return str_repeat(CIA::string($string), CIA::string($num));
	}

	static function js()
	{
		?>/*<script>*/

P$repeat = function($string, $num)
{
	var $str = '';
	$string = str($string);
	while (--$num>=0) $str += $string;
	return $str;
}

<?php	}
}

<?php

class
{
	static function php($string, $start, $length = false)
	{
		return false == $length
			? substr(CIA::string($string), $start)
			: substr(CIA::string($string), $start, $length);
	}

	static function js()
	{
		?>/*<script>*/

P$substr = function($string, $start, $length)
{
	$string = str($string);
	return t($length)
		? $string.substr($start, $length)
		: $string.substr($start);
}

<?php	}
}

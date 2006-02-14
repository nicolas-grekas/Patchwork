<?php

class pipe_substr
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

root.P$<?php echo substr(__CLASS__, 5)?> = function($string, $start, $length)
{
	$string = str($string);
	return t($length)
		? $string.substr($start, $length)
		: $string.substr($start);
}

<?php	}
}

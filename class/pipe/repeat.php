<?php

class pipe_repeat
{
	static function php($string, $num)
	{
		return str_repeat(CIA::string($string), CIA::string($num));
	}

	static function js()
	{
		?>/*<script>*/

root.P$<?php echo substr(__CLASS__, 5)?> = function($string, $num)
{
	var $str = '';
	$string = str($string);
	while (--$num>=0) $str += $string;
	return $str;
}

<?php	}
}

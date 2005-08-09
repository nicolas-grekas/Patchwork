<?php

class pipe_indent
{
	static function php($string, $chars = 4, $char = ' ')
	{
		return preg_replace('/^/mu', str_repeat(CIA::string($char), CIA::string($chars)), CIA::string($string));
	}

	static function js()
	{
		?>/*<script>*/

root.P<?php echo substr(__CLASS__, 5)?> = function($string, $chars, $char)
{
	$string = str($string);
	$chars = str($chars, 4);
	$char = str($char, ' ');

	var $char_repeated = $char;
	while (--$chars) $char_repeated += $char;

	return $char_repeated + $string.replace('/\n/g', '\n'+$char_repeated);
}

<?php	}
}

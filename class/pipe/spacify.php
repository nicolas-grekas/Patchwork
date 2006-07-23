<?php

class
{
	static function php($string, $spacify_char = ' ')
	{
		$string = preg_split("''u", CIA::string($string));
		$string = array_slice($string, 1, -1);
		return implode(CIA::string($spacify_char), $string);
	}

	static function js()
	{
		?>/*<script>*/

P$spacify = function($string, $spacify_char)
{
	return str($string).split('').join(str($spacify_char, ' '));
}

<?php	}
}

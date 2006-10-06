<?php

class
{
	static function php($string)
	{
		return preg_replace("/\b./eu", "mb_strtoupper('$0')", CIA::string($string));
	}

	static function js()
	{
		?>/*<script>*/

P$capitalize = function($string)
{
	$string = str($string).split(/\b/g);

	var $i = $string.length;
	while ($i--) $string[$i] = $string[$i].substr(0,1).toUpperCase() + $string[$i].substr(1);

	return $string.join('');
}
<?php 	}
}

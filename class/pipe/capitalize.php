<?php

class pipe_capitalize
{
	static function php($string)
	{
		return preg_replace("/\b./eu", "mb_strtoupper('$0')", CIA::string($string));
	}

	static function js()
	{
		?>/*<script>*/

root.P<?php echo substr(__CLASS__, 5)?> = function($string)
{
	return str($string).replace(/\b./g, function($a) {return $a.toUpperCase()});
}
<?php 	}
}

<?php

class pipe_default
{
	static function php($string, $default = '')
	{
		return '' != (string) $string ? $string : $default;
	}

	static function js()
	{
		?>/*<script>*/

root.P<?php echo substr(__CLASS__, 5)?> = function($string, $default)
{
	return $string>'' ? $string : $default;
}

<?php	}
}

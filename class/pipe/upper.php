<?php

class pipe_upper
{
	static function php($string)
	{
		return mb_strtoupper(CIA::string($string));
	}

	static function js()
	{
		?>/*<script>*/

root.P$<?php echo substr(__CLASS__, 5)?> = function($string)
{
	return str($string).toUpperCase();
}

<?php	}
}

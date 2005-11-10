<?php

class pipe_lower
{
	static function php($string)
	{
		return mb_strtolower(CIA::string($string));
	}

	static function js()
	{
		?>/*<script>*/

P<?php echo substr(__CLASS__, 5)?> = function($string)
{
	return str($string).toLowerCase();
}

<?php	}
}

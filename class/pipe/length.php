<?php

class pipe_length
{
	static function php($string)
	{
		return strlen(CIA::string($string));
	}

	static function js()
	{
		?>/*<script>*/

P$<?php echo substr(__CLASS__, 5)?> = function($string)
{
	return str($string).length;
}

<?php	}
}

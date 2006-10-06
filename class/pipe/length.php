<?php

class
{
	static function php($string)
	{
		return strlen(CIA::string($string));
	}

	static function js()
	{
		?>/*<script>*/

P$length = function($string)
{
	return str($string).length;
}

<?php	}
}

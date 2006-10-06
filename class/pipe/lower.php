<?php

class
{
	static function php($string)
	{
		return mb_strtolower(CIA::string($string));
	}

	static function js()
	{
		?>/*<script>*/

P$lower = function($string)
{
	return str($string).toLowerCase();
}

<?php	}
}

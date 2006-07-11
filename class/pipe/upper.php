<?php

class
{
	static function php($string)
	{
		return mb_strtoupper(CIA::string($string));
	}

	static function js()
	{
		?>/*<script>*/

P$upper = function($string)
{
	return str($string).toUpperCase();
}

<?php	}
}

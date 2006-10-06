<?php

class
{
	static function php($string)
	{
		CIA::setMaxage(1);
		CIA::setExpires('onmaxage');
		return $_SERVER['REQUEST_TIME'];
	}

	static function js()
	{
		?>/*<script>*/

P$now = function()
{
	return parseInt(new Date/1000);
}

<?php	}
}

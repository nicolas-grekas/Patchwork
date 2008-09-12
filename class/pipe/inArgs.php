<?php

class
{
	static function php($index)
	{
		$a = func_get_args();
		return isset($a[$index + 1]) ? $a[$index + 1] : '';
	}

	static function js()
	{
		?>/*<script>*/

P$inArgs = function($index)
{
	return P$inArgs.arguments[$index + 1] || '';
}

<?php	}
}

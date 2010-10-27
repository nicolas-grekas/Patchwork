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

function($index)
{
	return arguments[$index + 1] || '';
}

<?php	}
}

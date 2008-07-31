<?php

class
{
	static function php($index, $set)
	{
		$set = explode(mb_substr($set, 0, 1), $set);
		return isset($set[$index + 1]) ? $set[$index + 1] : $index;
	}

	static function js()
	{
		?>/*<script>*/

P$inSet = function($index, $set)
{
	$set = $set.split($set.charAt(0));
	return $set[$index + 1] || $index;
}

<?php	}
}

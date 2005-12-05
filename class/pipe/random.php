<?php

class pipe_random
{
	static function php($min = '', $max = '')
	{
		if ($max === '') $max = 32767;

		$min = (int) CIA::string($min);
		$max = (int) CIA::string($max);

		return mt_rand($min, $max);
	}

	static function js()
	{
		?>/*<script>*/

P<?php echo substr(__CLASS__, 5)?> = function($min, $max)
{
	if (!t($max))) $max = 32767;

	$min = ($min-0) || 0;
	$max -= 0;

	if ($min > $max)
	{
		var $tmp = $min;
		$min = $max;
		$max = $tmp;
	}

	return $min + parseInt(Math.random() * ($max+1));
}

<?php	}
}

<?php

class pipe_bytes
{
	static function php($byte)
	{
		$byte = CIA::string($byte);

		$suffix = ' Ko';

		if ($byte >= ($div=1073741824)) $suffix = ' Go';
		else if ($byte >= ($div=1048576)) $suffix = ' Mo';
		else $div = 1024;

		$byte /= $div;
		$div = $byte < 10 ? 100 : 1;
		$byte = intval($div*$byte)/$div;

		return $byte . $suffix;
	}

	static function js()
	{
		?>/*<script>*/

root.P<?php echo substr(__CLASS__, 5)?> = function($byte)
{
	$byte = str($byte);
	var $suffix = ' Ko', $div;

	if ($byte >= ($div=1073741824)) $suffix = ' Go';
	else if ($byte >= ($div=1048576)) $suffix = ' Mo';
	else $div = 1024;

	$byte /= $div;
	$div = $byte < 10 ? 100 : 1;
	$byte = parseInt($div*$byte)/$div;

	return $byte + $suffix;
}

<?php	}
}

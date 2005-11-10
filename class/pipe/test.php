<?php

class pipe_test
{
	static function php($test, $ifData, $elseData = '')
	{
		return CIA::string($test) ? $ifData : $elseData;
	}

	static function js()
	{
		?>/*<script>*/

P<?php echo substr(__CLASS__, 5)?> = function($test, $ifData, $elseData)
{
	return num(str($test)) ? $ifData : $elseData;
}

<?php	}
}

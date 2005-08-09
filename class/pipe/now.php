<?php

class pipe_now
{
	static function php($string)
	{
		CIA::setCacheControl(1, false, true);
		return CIA_TIME;
	}

	static function js()
	{
		?>/*<script>*/

root.P<?php echo substr(__CLASS__, 5)?> = function()
{
	return parseInt(new Date/1000);
}

<?php	}
}

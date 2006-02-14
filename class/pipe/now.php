<?php

class pipe_now
{
	static function php($string)
	{
		CIA::setMaxage(1);
		CIA::setExpires(true);
		return CIA_TIME;
	}

	static function js()
	{
		?>/*<script>*/

root.P$<?php echo substr(__CLASS__, 5)?> = function()
{
	return parseInt(new Date/1000);
}

<?php	}
}

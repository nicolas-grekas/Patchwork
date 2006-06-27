<?php

class pipe_void
{
	static function php()
	{
		return '';
	}

	static function js()
	{
		?>/*<script>*/

P$<?php echo substr(__CLASS__, 5)?> = function()
{
	return '';
}
<?php 	}
}

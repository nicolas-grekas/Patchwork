<?php

class pipe_empty
{
	static function php()
	{
		return '';
	}

	static function js()
	{
		?>/*<script>*/

P<?php echo substr(__CLASS__, 5)?> = function()
{
	return '';
}
<?php 	}
}

<?php

// This pipe is duplicated in js/w

class pipe_home
{
	static function php($string = '')
	{
		return CIA::home( CIA::string($string) );
	}

	static function js()
	{
		?>/*<script>*/

P$<?php echo substr(__CLASS__, 5)?> = function($string)
{
	return home( str($string) );
}

<?php	}
}

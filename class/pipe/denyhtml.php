<?php

class pipe_denyhtml
{
	static function php($string)
	{
		return htmlspecialchars( CIA::string($string) );
	}

	static function js()
	{
		?>/*<script>*/

P$<?php echo substr(__CLASS__, 5)?> = function($string)
{
	return esc( str($string) );
}

<?php	}
}

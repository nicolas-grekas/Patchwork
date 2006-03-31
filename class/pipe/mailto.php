<?php

class pipe_mailto
{
	static function php($string)
	{
		$string = htmlspecialchars( CIA::string($string) );

		return '<a href="mailto:'
			. str_replace('@', '[&#97;t]', $string) . '">'
			. str_replace('@', '<span style="display:none">@</span>&#64;', $string)
			. '</a>';
	}

	static function js()
	{
		?>/*<script>*/

P$<?php echo substr(__CLASS__, 5)?> = function($string)
{
	$string = esc( str($string) );

	return '<a href="mailto:' + $string + '">' + $string + '</a>';
}

<?php	}
}

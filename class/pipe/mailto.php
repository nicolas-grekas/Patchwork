<?php

class
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

P$mailto = function($string)
{
	$string = esc( str($string) );

	return '<a href="mailto:' + $string + '">' + $string + '</a>';
}

<?php	}
}

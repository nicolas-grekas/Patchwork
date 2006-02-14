<?php

class pipe_nl2br
{
	static function php($string)
	{
		return nl2br(CIA::string($string));
	}

	static function js()
	{
		?>/*<script>*/

P$<?php echo substr(__CLASS__, 5)?> = function($string)
{
	return str($string).replace(/\n/g, '\n<br />');
}

<?php	}
}

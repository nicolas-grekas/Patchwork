<?php

class
{
	static function php($string)
	{
		return nl2br(CIA::string($string));
	}

	static function js()
	{
		?>/*<script>*/

P$nl2br = function($string)
{
	return str($string).replace(/\n/g, '\n<br />');
}

<?php	}
}

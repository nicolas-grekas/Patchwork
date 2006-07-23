<?php // vim: set enc=utf-8 ai noet ts=4 sw=4 fdm=marker:

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

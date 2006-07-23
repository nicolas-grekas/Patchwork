<?php // vim: set enc=utf-8 ai noet ts=4 sw=4 fdm=marker:

class
{
	static function php($string)
	{
		return strlen(CIA::string($string));
	}

	static function js()
	{
		?>/*<script>*/

P$length = function($string)
{
	return str($string).length;
}

<?php	}
}

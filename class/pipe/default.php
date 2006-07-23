<?php // vim: set enc=utf-8 ai noet ts=4 sw=4 fdm=marker:

class
{
	static function php($string, $default = '')
	{
		return '' != (string) $string ? $string : $default;
	}

	static function js()
	{
		?>/*<script>*/

P$default = function($string, $default)
{
	return $string>'' ? $string : $default;
}

<?php	}
}

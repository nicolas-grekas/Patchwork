<?php // vim: set enc=utf-8 ai noet ts=4 sw=4 fdm=marker:

// This pipe is duplicated in js/w

class
{
	static function php($string = '')
	{
		return CIA::home( CIA::string($string) );
	}

	static function js()
	{
		?>/*<script>*/

P$home = function($string)
{
	return home( str($string) );
}

<?php	}
}

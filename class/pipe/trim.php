<?php // vim: set enc=utf-8 ai noet ts=4 sw=4 fdm=marker:

class
{
	static function php($a)
	{
		return trim( CIA::string($a) );
	}

	static function js()
	{
		?>/*<script>*/

P$trim = function($a)
{
	return str($a).replace(/^\s+/, '').replace(/\s+$/, '');
}

<?php	}
}

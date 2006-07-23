<?php // vim: set enc=utf-8 ai noet ts=4 sw=4 fdm=marker:

class
{
	static function php($str)
	{
		return rawurlencode(CIA::string($str));
	}

	static function js()
	{
		?>/*<script>*/

P$urlencode = function($str)
{
	return eUC(str($str));
}

<?php	}
}

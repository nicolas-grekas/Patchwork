<?php // vim: set enc=utf-8 ai noet ts=4 sw=4 fdm=marker:

class
{
	static function php($test, $ifData, $elseData = '')
	{
		return CIA::string($test) ? $ifData : $elseData;
	}

	static function js()
	{
		?>/*<script>*/

P$test = function($test, $ifData, $elseData)
{
	return num(str($test), 1) ? $ifData : $elseData;
}

<?php	}
}

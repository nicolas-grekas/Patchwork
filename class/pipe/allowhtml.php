<?php // vim: set enc=utf-8 ai noet ts=4 sw=4 fdm=marker:

class
{
	static function php($string)
	{
		$string = CIA::string($string);

		return (string) $string === (string) ($string-0)
			? $string
			: str_replace(
				array('&#039;', '&quot;', '&gt;', '&lt;', '&amp;'),
				array("'"     , '"'     , '>'   , '<'   , '&'    ),
				$string
			);
	}

	static function js()
	{
		?>/*<script>*/

P$allowhtml = function($string)
{
	$string = str($string);
	return (''+$string/1==$string)
		? $string/1
		: unesc($string);
}

<?php	}
}

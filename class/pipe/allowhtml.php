<?php

class pipe_allowhtml
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

P$<?php echo substr(__CLASS__, 5)?> = function($string)
{
	$string = str($string);
	return (''+$string/1==$string)
		? $string/1
		: unesc($string);
}

<?php	}
}

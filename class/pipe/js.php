<?php

class pipe_js
{
	static function php($string)
	{
		$string = CIA::string($string);

		return (string) $string === (string) ($string-0)
			? $string
			: ("'" . str_replace(
					array('&#039;', '&quot;', '&gt;', '&lt;', '&amp;', '\\'  , "'"  , "\r" , "\n" , '</' ),
					array("'"     , '"'     , '>'   , '<'   , '&'    , '\\\\', "\\'", '\\r', '\\n', '<\/'),
					$string
				) . "'"
			);
	}

	static function js()
	{
		?>/*<script>*/

P$<?php echo substr(__CLASS__, 5)?> = function($string)
{
	$string = str($string);

	return (''+$string/1 == $string)
		? $string/1
		: ("'" + $string.replace(
				/&#039;/g, "'").replace(
				/&quot;/g, '"').replace(
				/&gt;/g  , '>').replace(
				/&lt;/g  , '<').replace(
				/&amp;/g , '&').replace(
				/\\/g , '\\\\').replace(
				/'/g  , "\\'").replace(
				/\r/g , '\\r').replace(
				/\n/g , '\\n').replace(
				/<\//g, '<\/'
			) + "'"
		);
}

<?php	}
}

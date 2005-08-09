<?php

class pipe_replace
{
	static function php($string, $search, $replace, $caseInsensitive = false)
	{
		$search = CIA::string($search);
		$caseInsensitive = CIA::string($caseInsensitive) ? 'i' : '';
		return preg_replace("/$search/u$caseInsensitive", CIA::string($replace), CIA::string($string));
	}

	static function js()
	{
		?>/*<script>*/

root.P<?php echo substr(__CLASS__, 5)?> = function($string, $search, $replace, $caseInsensitive)
{
	$search = new RegExp(str($search), 'g' + (str($caseInsensitive) ? 'i' : ''));
	return str($string).replace($search, str($replace));
}

<?php	}
}

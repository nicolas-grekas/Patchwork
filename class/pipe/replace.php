<?php // vim: set enc=utf-8 ai noet ts=4 sw=4 fdm=marker:

class
{
	static function php($string, $search, $replace, $caseInsensitive = false)
	{
		$search = str_replace('@', '\\@', CIA::string($search));
		$caseInsensitive = CIA::string($caseInsensitive) ? 'i' : '';
		return preg_replace("@{$search}@su{$caseInsensitive}", CIA::string($replace), CIA::string($string));
	}

	static function js()
	{
		?>/*<script>*/

P$replace = function($string, $search, $replace, $caseInsensitive)
{
	$search = new RegExp(str($search), 'g' + (str($caseInsensitive) ? 'i' : ''));
	return str($string).replace($search, str($replace));
}

<?php	}
}

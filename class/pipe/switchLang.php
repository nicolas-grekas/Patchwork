<?php

class pipe_switchLang
{
	static function php($g, $lang)
	{
		$url = explode("/{$g->__LANG__}/", $g->__URI__, 2);
		$url = implode("/{$lang}/", $url);

		return $url;
	}

	static function js()
	{
		?>/*<script>*/

P$<?php echo substr(__CLASS__, 5)?> = function($g, $lang)
{
	return $g.__URI__.replace(new RegExp('/' + $g.__LANG__ + '/'), '/' + $lang + '/');
}

<?php	}
}

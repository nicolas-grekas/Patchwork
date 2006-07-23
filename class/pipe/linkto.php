<?php // vim: set enc=utf-8 ai noet ts=4 sw=4 fdm=marker:

class
{
	static function php($text, $url, $attributes = '')
	{
		$text = CIA::string($text);
		$url = CIA::string($url);

		$a = strpos($url, '#');
		if (false !== $a)
		{
			$hash = substr($url, $a);
			$url = substr($url, 0, $a);
		}
		else $hash = '';

		return $url == htmlspecialchars(substr(CIA::__URI__(), strlen(CIA::__HOME__())))
			? ('<b class="linkloop">' . $text . '</b>')
			: ('<a href="' . CIA::home($url) . $hash . '" ' . CIA::string($attributes) . '>' . $text . '</a>');
	}

	static function js()
	{
		?>/*<script>*/

P$linkto = function($text, $url, $attributes)
{
	$text = str($text);
	$url = str($url);

	var $a = $url.indexOf('#'), $hash;
	if ($a >= 0)
	{
		$hash = $url.substr($a);
		$url = $url.substr(0, $a);
	}
	else $hash = '';

	return $url == esc(''+location).substr(home('',1).length)
			? ('<b class="linkloop">' + $text + '</b>')
			: ('<a href="' + home($url) + $hash + '" ' + str($attributes) + '>' + $text + '</a>');
}

<?php	}
}

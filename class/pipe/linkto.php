<?php

class pipe_linkto
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

		return $url == htmlspecialchars(substr(CIA::__URI__(), strlen(CIA::__ROOT__())))
			? ('<b class="linkloop">' . $text . '</b>')
			: ('<a href="' . CIA::root($url) . $hash . '" ' . CIA::string($attributes) . '>' . $text . '</a>');
	}

	static function js()
	{
		?>/*<script>*/

P$<?php echo substr(__CLASS__, 5)?> = function($text, $url, $attributes)
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

	return $url == _GET.__URI__.substr(_GET.__ROOT__.length)
			? ('<b class="linkloop">' + $text + '</b>')
			: ('<a href="' + root($url) + $hash + '" ' + str($attributes) + '>' + $text + '</a>');
}

<?php	}
}

<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 3 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


class
{
	static function php($string, $email = '', $attributes = '')
	{
		static $first = true;

		$string = htmlspecialchars(p::string($string));
		$email  = htmlspecialchars(p::string($email));
		if (!$email) $email = $string;
		$attributes = htmlspecialchars(p::string($attributes));
		'' !== $attributes && $attributes = ' ' . $attributes;

		$email = '<a name="mailA' . PATCHWORK_PATH_TOKEN . '" href="mailto:'
			. str_replace('@', '[&#97;t]', $email) . '"'
			. $attributes . '>'
			. str_replace('@', '<span style="display:none" name="mailS' . PATCHWORK_PATH_TOKEN . '">@</span>&#64;', $string)
			. '</a>';

		if ($first)
		{
			$first = false;
			$email .= '<script type="text/javascript" name="w$">/*<![CDATA[*/document.getElementsByName && onDOMLoaded.push(function()
{
	var i, l

	a = document.getElementsByName("mailA' . PATCHWORK_PATH_TOKEN . '");
	for (i=0, len=a.length; i<len; ++i) a[i].href = a[i].href.replace(/\\[at\\]/, "@");

	a = document.getElementsByName("mailS' . PATCHWORK_PATH_TOKEN . '");
	for (i=0, len=a.length; i<len; ++i) a[i].parentNode.removeChild(a[i]);
}
)//]]></script>';
		}

		return $email;
	}

	static function js()
	{
		?>/*<script>*/

P$mailto = function($string, $email, $attributes)
{
	$string = esc(str($string));
	$email  = esc(str($email)) || $string;
	$attributes = esc(str($attributes));
	if ($attributes) $attributes = ' ' + $attributes;

	return '<a href="mailto:' + $email + '"' + $attributes + '>' + $string + '</a>';
}

<?php	}
}

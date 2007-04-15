<?php /*********************************************************************
 *
 *   Copyright : (C) 2006 Nicolas Grekas. All rights reserved.
 *   Email     : nicolas.grekas+patchwork@espci.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL, see COPYING
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


class
{
	static function php($string, $email = '', $attributes = '')
	{
		$string = htmlspecialchars(CIA::string($string));
		$email  = htmlspecialchars(CIA::string($email));
		if (!$email) $email = $string;
		$attributes = htmlspecialchars(CIA::string($attributes));
		'' !== $attributes && $attributes = ' ' . $attributes;


		return '<a href="mailto:'
			. str_replace('@', '[&#97;t]', $email) . '"'
			. $attributes . '>'
			. str_replace('@', '<span style="display:none">@</span>&#64;', $string)
			. '</a>';
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

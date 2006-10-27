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
	static $mailrx = '/(\s)([-a-z0-9_\.\+=]+)@([-a-z0-9]+(\.[-a-z0-9]+)+)/i';
	static $phonerx = '/(\s)(\+|00)([-0-9. ()]+[0-9])/';
	static $url1rx = '/(\s)([-+a-z]+:\\/\\/[-a-z0-9_.!~*\'(),\\/?:@&=+$#]+)/i';
	static $url2rx = '/(\s)([-a-z0-9_]{2,}(\.[-a-z0-9_]{2,})+[-a-z0-9_.!~*\'(),\\/?:@&=+$#]*)/i';

	static function php($string)
	{
		$string = ' ' . CIA::string($string);

		$string = preg_replace(
			self::$mailrx . 'u',
			'$1<a href="mailto:$2[&#97;t]$3">$2<span style="display:none">@</span>&#64;$3</a>',
			$string
		);

		$string = preg_replace(
			self::$phonerx . 'u',
			'$1<a href="callto:+$3" target="_blank">+$3</a>',
			$string
		);

		$string = preg_replace(
			self::$url1rx . 'u',
			'$1<a href="$2" target="_blank">$2</a>',
			$string
		);

		$string = preg_replace(
			self::$url2rx . 'u',
			'$1<a href="http://$2" target="_blank">$2</a>',
			$string
		);

		return substr($string, 1);
	}

	static function js()
	{
		?>/*<script>*/

P$urlize = function($string)
{
	return (' ' + str($string)).replace(
		<?php echo self::$mailrx?>g, '$1<a href="mailto:$2@$3">$2@$3</a>'
	).replace(
		<?php echo self::$phonerx?>g, '$1<a href="callto:+$3" target="_blank">+$3</a>'
	).replace(
		<?php echo self::$url1rx?>g, '$1<a href="$2" target="_blank">$2</a>'
	).replace(
		<?php echo self::$url2rx?>g, '$1<a href="http://$2" target="_blank">$2</a>'
	).substr(1);
}

<?php	}
}

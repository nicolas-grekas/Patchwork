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
	static $mailrx = '/(\s)([-a-z\d_\.\+=]+)@([-a-z\d]+(\.[-a-z\d]+)+)/i';
	static $httprx = '/(\s)(http(s?):\/\/)?(((((([a-z\d]([-a-z\d]*[a-z\d])?)\.)+[a-z]{2,3})|(\d+(\.\d+){3}))(:\d+)?)((\/([-a-z\d$_.+!*\'\[\],;:@&=?#]|%[a-f\d]{2})+)+|\/))/i';

	static function php($string)
	{
		$string = ' ' . CIA::string($string);

		$string = preg_replace(
			self::$mailrx . 'u',
			'$1<a href="mailto:$2[&#97;t]$3"><span style="display:none">@</span>&#64;$3</a>',
			$string
		);

		$string = preg_replace(
			self::$httprx . 'u',
			'$1<a href="http$3://$4">$4</a>',
			$string
		);

		return substr($string, 1);
	}

	static function js()
	{
		?>/*<script>*/

P$urlize = function($string)
{
	return (' '+$string).replace(
		<?php echo self::$mailrx?>g, '$1<a href="mailto:$2@$3">$2@$3</a>'
	).replace(
		<?php echo self::$httprx?>g, '$1<a href="http$3://$4">$4</a>'
	).substr(1);
}

<?php	}
}

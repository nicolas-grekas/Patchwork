/***************************************************************************
 *
 *	Copyright : (C) 2006 Nicolas Grekas. All rights reserved.
 *	Email	  : nicolas.grekas+patchwork@espci.org
 *	License	: http://www.gnu.org/licenses/gpl.txt GNU/GPL, see COPYING
 *
 *	This program is free software; you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation; either version 2 of the License, or
 *	(at your option) any later version.
 *
 *	Original version by Tyler Akins
 *	http://rumkin.com
 *
 ***************************************************************************/


base64 = {
	$char: [],
	$code: [],

	encode: function($input)
	{
		$input = unescape(encodeURI($input));

		var $output = [],
			$i = 0,
			$len = $input.length,
			$chr1, $chr2, $chr3,
			$enc1, $enc2, $enc3, $enc4,
			$code = base64.$code;

		while ($i < $len)
		{
			$chr1 = $input.charCodeAt($i++);
			$chr2 = $input.charCodeAt($i++);
			$chr3 = $input.charCodeAt($i++);

			$enc1 = $chr1 >> 2;
			$enc2 = (($chr1 &  3) << 4) | ($chr2 >> 4);
			$enc3 = (($chr2 & 15) << 2) | ($chr3 >> 6);
			$enc4 = $chr3 & 63;

			     if (isNaN($chr2)) $enc3 = $enc4 = 64;
			else if (isNaN($chr3)) $enc4 = 64;

			$output.push($char[$enc1] + $char[$enc2] + $char[$enc3] + $char[$enc4]);
		}
		
		return $output.join('');
	},

	decode: function($input)
	{
		var $output = [],
			$i = 0,
			$len = $input.length,
			$chr1, $chr2, $chr3,
			$enc1, $enc2, $enc3, $enc4,
			$code = base64.$code;

		while ($i < $len)
		{
			$enc1 = $code[$input.charAt($i++)];
			$enc2 = $code[$input.charAt($i++)];
			$enc3 = $code[$input.charAt($i++)];
			$enc4 = $code[$input.charAt($i++)];

			$chr1 = ($enc1 << 2) | ($enc2 >> 4);
			$chr2 = (($enc2 & 15) << 4) | ($enc3 >> 2);
			$chr3 = (($enc3 &  3) << 6) | $enc4;

			$output.push($enc1 = String.fromCharCode($chr1, $chr2, $chr3));
		}

		$len /= 4;

		if ($len--)
		{
			     if (64 == $chr2) $output[$len] = $output[$len].substr(0, 1);
			else if (64 == $chr3) $output[$len] = $output[$len].substr(0, 2);
		}

		$output = $output.join('');

		return decodeURIComponent(escape($output));
	}

	$init: function()
	{
		var $char = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=',
			$i = 0, $c;

		do base64.$code[ base64.$char[$i] = $c = $char.charAt($i) ] = $c;
		while (++$i < 65);
	}
}

base64.$init();

{*/**************************************************************************
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
 **************************************************************************/*}


/*
* Set a board variable
*/
function setboard($name, $value, $window)
{
	if (t($name, 'object')) for ($value in $name) setboard($value, $name[$value], $window);
	else
	{
		$window = $window || topwin;

		function $escape($str)
		{
			return eUC(''+$str).replace(
				/_/g, '_5F').replace(
				/!/g, '_21').replace(
				/'/g, '_27').replace(
				/\(/g, '_28').replace(
				/\)/g, '_29').replace(
				/\*/g, '_30').replace(
				/-/g, '_2D').replace(
				/\./g, '_2E').replace(
				/~/g, '_7E').replace(
				/%/g, '_'
			);
		}

		$name = '_K' + $escape($name) + '_V';

		var $winName = $window.name,
			$varIdx = $winName.indexOf($name),
			$varEndIdx;

		if ($varIdx>=0)
		{
			$varEndIdx = $winName.indexOf('_K', $varIdx + $name.length);
			$winName = $winName.substring(0, $varIdx) + ( $varEndIdx>=0 ? $winName.substring($varEndIdx) : '' );
		}

		$window.name = $winName + $name + $escape($value);
		$window = 0;
	}
}


if (!window.BOARD)
{
	if ((topwin = window).Error)
		// This eval avoids a parse error with browsers not supporting exceptions.
		eval('try{while(((i=topwin.parent)!=topwin)&&t(i.name))topwin=i}catch(i){}');

	BOARD = {};
	i = topwin.name.indexOf('_K');

	if (0 <= i)
	{
		i = parseurl(
			topwin.name.substr(i).replace(
				/_K/g, '&').replace(
				/_V/g, '=').replace(
				/_/g , '%')
			, '&'
		);

		l = location;
		l = l.protocol + ':' + l.hostname;

		if (i.$ != l) topwin.name = '', setboard('$', l);
		else BOARD = i;
	}
}

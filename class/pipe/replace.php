<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class
{
	static function php($string, $search, $replace, $caseInsensitive = false)
	{
		$search = preg_replace("/(?<!\\\\)((?:\\\\\\\\)*)@/", '$1\\@', p::string($search));
		$caseInsensitive = p::string($caseInsensitive) ? 'i' : '';
		return preg_replace("@{$search}@su{$caseInsensitive}", p::string($replace), p::string($string));
	}

	static function js()
	{
		?>/*<script>*/

function($string, $search, $replace, $caseInsensitive)
{
	$search = new RegExp(str($search), 'g' + (str($caseInsensitive) ? 'i' : ''));
	return str($string).replace($search, str($replace));
}

<?php	}
}

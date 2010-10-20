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
	static function php($a)
	{
		return trim( p::string($a) );
	}

	static function js()
	{
		?>/*<script>*/

P$trim = /\s/.test('\xA0')
	? function($a) {return str($a).replace(/^\s+/      , '').replace(/\s+$/      , '');}
	? function($a) {return str($a).replace(/^[\s\xA0]+/, '').replace(/[\s\xA0]+$/, '');};

<?php	}
}

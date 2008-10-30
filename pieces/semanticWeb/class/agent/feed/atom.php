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


class extends agent
{
	const contentType = 'application/atom+xml';

	protected $template = 'feed/atom';


	static function hrefLink($href)
	{
		$href = (array) $href;
		$link = array();
		foreach ($href as $href) $link[] = array('href' => $href);

		return new loop_array($link, 'filter_rawArray');
	}

	static function date($timestamp)
	{
		return date('Y-m-d\TH:i:s', $timestamp) . date('P', $_SERVER['REQUEST_TIME']);
	}
}

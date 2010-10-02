<?php /*********************************************************************
 *
 *   Copyright : (C) 2010 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class patchwork_tokenizer_T extends patchwork_tokenizer
{
	protected $callbacks = array('tagT' => array('T' => T_USE_FUNCTION));

	function tagT(&$token)
	{
		// TODO: fetch constant code and add it to the translation table
		new patchwork_tokenizer_bracket_T($this);
	}
}

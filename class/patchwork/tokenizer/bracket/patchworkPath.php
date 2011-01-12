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


class patchwork_tokenizer_bracket_patchworkPath extends patchwork_tokenizer_bracket
{
	protected $level;

	function __construct(patchwork_tokenizer $parent, $level)
	{
		$this->level = (string) (int) $level;
		parent::__construct($parent);
	}

	protected function onClose(&$token)
	{
		2 === $this->bracketIndex && $token[1] = ',' . $this->level . $token[1];
	}
}

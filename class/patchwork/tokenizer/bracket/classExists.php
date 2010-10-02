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


class patchwork_tokenizer_bracket_classExists extends patchwork_tokenizer_bracket
{
	function onReposition(&$token)
	{
		if (1 === $this->bracketPosition) $token[1] .= '(';
		if (2 === $this->bracketPosition) $token[1] = ')||1' . $token[1];
	}

	function onClose(&$token)
	{
		if (1 === $this->bracketPosition) $token[1] = ')||1' . $token[1];
	}
}

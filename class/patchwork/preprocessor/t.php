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


class extends patchwork_preprocessor_bracket
{
	function filterBracket($type, $token)
	{
		switch ($type)
		{
		case T_CURLY_OPEN:
		case T_DOLLAR_OPEN_CURLY_BRACES:
		case '.':
			patchwork_preprocessor::error(
				"Usage of T() is potentially divergent, please use sprintf() instead of string concatenation.",
				$this->preproc->source, $this->preproc->line
			);
		}

		return $token;
	}
}

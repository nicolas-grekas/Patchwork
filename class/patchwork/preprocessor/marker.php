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
	public

	$greedy = false,
	$curly = 0;


	function filterPreBracket($type, $token) {return $this->filter($type, $token);}
	function filterBracket   ($type, $token) {return $this->filter($type, $token);}
	function onClose($token)                 {return $this->filter(')'  , $token);}

	function filter($type, $token)
	{
		if (T_WHITESPACE === $type || T_COMMENT === $type || T_DOC_COMMENT === $type)
		{
			// Do nothing
		}
		else if (0 <= $this->curly) switch ($type)
		{
			case '$': break;
			case '{': ++$this->curly; break;
			case '}': --$this->curly; break;
			default: 0 < $this->curly || $this->curly = -1;
		}
		else
		{
			if (!$this->greedy &&'?' === $type) --$this->bracket;

			switch ($type)
			{
			case '{':
			case '[':
			case '?': ++$this->bracket; break;
			case ',': if ($this->bracket) break;
			case '}':
			case ']':
			case ':': if ($this->bracket--) break;
			case ')': if (0 <= $this->bracket) break;
			case T_AS: case T_CLOSE_TAG: case ';':
				$token = ':0)' . $token;
				$this->popFilter();
				return $token;
			}

			if ($this->greedy) return $token;
			if (':' === $type) ++$this->bracket;
			if (0 < $this->bracket) return $token;

			switch ($type)
			{
			case ')':
			case '}':
			case ']':
			case T_INC:
			case T_DEC:
				break;

			case T_OBJECT_OPERATOR:
				$this->curly = 0;

			case '=':
			case T_DIV_EQUAL:
			case T_MINUS_EQUAL:
			case T_MOD_EQUAL:
			case T_MUL_EQUAL:
			case T_PLUS_EQUAL:
			case T_SL_EQUAL:
			case T_SR_EQUAL:
			case T_XOR_EQUAL:
			case T_AND_EQUAL:
			case T_OR_EQUAL:
			case T_CONCAT_EQUAL:
				$this->greedy = true;
				break;

			default:
				$token = ':0)' . $token;
				$this->popFilter();
			}
		}

		return $token;
	}
}

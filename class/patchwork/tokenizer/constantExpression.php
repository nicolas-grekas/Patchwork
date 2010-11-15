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


// TODO: refactor to re-enable this feature

class patchwork_tokenizer_constantExpression
{
	protected static $variableType = array(
		T_EVAL, T_LINE, T_FILE, T_DIR, T_FUNC_C, T_CLASS_C,
		T_METHOD_C, T_NS_C, T_INCLUDE, T_REQUIRE, T_GOTO,
		T_CURLY_OPEN, T_VARIABLE, '$', T_INCLUDE_ONCE,
		T_REQUIRE_ONCE, T_DOLLAR_OPEN_CURLY_BRACES, T_EXIT,
	);


	static function fetch($tokens, &$i, $count, &$value)
	{
		$new_code = array();
		$bracket = 0;
		$close = 0;

		for ($j = $i; $j < $count; ++$j)
		{
			list($type, $code, $sugar) = $tokens[$j] + array(2 => '');

			switch ($type)
			{
			case '`':
			case T_STRING:
			case T_NS_SEPARATOR:
				$close = 2;
				break;

			case '?': case '(': case '{': case '[':
				++$bracket;
				break;

			case ':': case ')': case '}': case ']':
				$bracket-- || ++$close;
				break;

			case ',':
				$bracket   || ++$close;
				break;

			case T_AS:
			case T_CLOSE_TAG:
			case ';':
				++$close;
				break;

			case T_WHITESPACE: break;

			default:
				if (in_array($type, self::$variableType, true)) $close = 2;
			}

			if (1 === $close)
			{
				$i = $j;
				$j = implode('', $new_code);
				return false === @eval("\$value={$j};") ? null : $j;
			}
			else if (2 === $close)
			{
				return;
			}
			else $new_code[] = $sugar . $code;
		}
	}
}

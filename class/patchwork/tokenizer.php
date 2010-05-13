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


// New tokens since PHP 5.3
defined('T_GOTO')         || define('T_GOTO', -1);
defined('T_USE' )         || define('T_USE' , -1);
defined('T_DIR' )         || define('T_DIR' , -1);
defined('T_NS_C')         || define('T_NS_C', -1);
defined('T_NAMESPACE')    || define('T_NAMESPACE', -1);
defined('T_NS_SEPARATOR') || define('T_NS_SEPARATOR', -1);


class patchwork_tokenizer
{
	protected static $variableType = array(
		T_EVAL, '(', T_LINE, T_FILE, T_DIR, T_FUNC_C, T_CLASS_C, T_METHOD_C, T_NS_C, T_INCLUDE, T_REQUIRE,
		T_CURLY_OPEN, T_VARIABLE, '$', T_INCLUDE_ONCE, T_REQUIRE_ONCE, T_DOLLAR_OPEN_CURLY_BRACES, T_EXIT,
	);

	static function getAll($code, $strip)
	{
		$tokens = array();
		$line = 1;
		$inString = 0;
		$deco = '';

		$code = token_get_all($code);
		$length = count($code);
		$i = 0;

		while ($i < $length)
		{
			if (is_array($code[$i]))
			{
				$token = $code[$i];

				if (isset($token[2])) $line = $token[2];
				else $token[2] = $line;

				switch ($token[0])
				{
				case T_INLINE_HTML:
				case T_CONSTANT_ENCAPSED_STRING:
				case T_ENCAPSED_AND_WHITESPACE:
					$line += substr_count($token[1], "\n");
					break;

				case T_CURLY_OPEN:
				case T_DOLLAR_OPEN_CURLY_BRACES:
				case T_START_HEREDOC: ++$inString; break;
				case T_END_HEREDOC:   --$inString; break;
				case T_STRING:
					if ($inString & 1) $token[0] = T_ENCAPSED_AND_WHITESPACE;
					break;

				case T_OPEN_TAG_WITH_ECHO: // Replace <?= by <?php echo
					$token[1] = '<?php ' . str_repeat("\n", substr_count($token[1], "\n"));
					$token[0] = T_OPEN_TAG;

					$code[  $i] = array(T_ECHO, 'echo');
					$code[--$i] = $token;
					continue 2;

				case T_OPEN_TAG: // Normalize PHP open tag
					$lines = substr_count($token[1], "\n");
					$token[1] = '<?php ' . str_repeat("\n", $lines);
					$line += $lines;
					break;

				case T_CLOSE_TAG: // Normalize PHP close tag
					$lines = substr_count($token[1], "\n");
					$token[1] = str_repeat("\n", $lines) . '?>';
					$line += $lines;
					break;
				}
			}
			else
			{
				switch ($t = $code[$i])
				{
				case '"':
				case '`':
					if ($inString & 1) --$inString;
					else ++$inString;

					$token = array($t, $t, $line);
					break;

				case '}':
					if ($inString)
					{
						--$inString;
						$token = array(T_ENCAPSED_AND_WHITESPACE, '}', $line);
					}
					else $token = array('}', '}', $line);

					break;

				default:
					$token = array(
						($inString & 1) ? T_ENCAPSED_AND_WHITESPACE : $t,
						$t,
						$line
					);
				}
			}

			unset($code[$i++]);

			if ('' !== $deco)
			{
				$token[3] = $deco;
				$deco = '';
			}

			$tokens[] = $token;

			while ($i < $length && in_array($code[$i][0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT), true))
			{
				$lines = substr_count($code[$i][1], "\n");

				if ($strip)
				{
					if (T_DOC_COMMENT === $code[$i][0])
					{
						$deco = str_repeat("\n", substr_count($deco, "\n"));
					}
					else
					{
						$code[$i][1] = $lines ? str_repeat("\n", $lines) : ' ';
					}

					$deco .= $code[$i][1];
				}
				else $tokens[] = $code[$i];

				$line += $lines;

				unset($code[$i++]);
			}
		}

		return $tokens;
	}

	static function fetchConstantCode($tokens, &$i, $count, &$value)
	{
		$variableType = self::$variableType;
		$new_code = array();
		$bracket = 0;
		$close = 0;

		for ($j = $i; $j < $count; ++$j)
		{
			list($type, $code, , $deco) = $tokens[$j] + array(3 => '');

			switch ($type)
			{
			case '`':
			case T_STRING:
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

			default:
				if (in_array($type, $variableType, true)) $close = 2;
			}

			if (1 === $close)
			{
				$i = $j - 1;
				$j = implode('', $new_code);
				return false === @eval("\$value={$j};") ? null : $j;
			}
			else if (2 === $close)
			{
				return;
			}
			else $new_code[] = $deco . $code;
		}
	}
}

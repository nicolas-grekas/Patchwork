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


class patchwork_tokenizer__0
{
	static function getAll($code, $strip)
	{
		$tokens = array();
		$line = 1;
		$inString = 0;
		$sugar = '';

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

			$token[3] = $sugar;
			$tokens[] = $token;
			$sugar = '';

			while ($i < $length && (T_WHITESPACE === $code[$i][0] || T_COMMENT === $code[$i][0] || T_DOC_COMMENT === $code[$i][0]))
			{
				$lines = substr_count($code[$i][1], "\n");

				if ($strip)
				{
					if (T_DOC_COMMENT === $code[$i][0])
					{
						$sugar = str_repeat("\n", substr_count($sugar, "\n"));
					}
					else
					{
						$code[$i][1] = $lines ? str_repeat("\n", $lines) : ' ';
					}
				}

				$line += $lines;
				$sugar .= $code[$i][1];
				unset($code[$i++]);
			}
		}

		return $tokens;
	}
}

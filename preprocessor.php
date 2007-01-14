<?php /*********************************************************************
 *
 *   Copyright : (C) 2006 Nicolas Grekas. All rights reserved.
 *   Email     : nicolas.grekas+patchwork@espci.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL, see COPYING
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


function_exists('token_get_all') || die('Extension "tokenizer" is needed and not loaded.');
class_exists('Reflection', 0) || die('Extension "Reflection" is needed and not loaded.');


function stripPHPWhiteSpaceNComments($a)
{
	$a = preg_replace(
		array("'[^\r\n]+'", "' +([\r\n])'", "'([\r\n]) +'"),
		array(' '         , '$1'          , '$1'          ),
		$a
	);

	return $a;
}

function fetchPHPWhiteSpaceNComments(&$source, &$i)
{
	$token = '';

	while (
		isset($source[++$i]) && is_array($source[$i]) && ($t = $source[$i][0])
		&& (T_COMMENT == $t || T_WHITESPACE == $t || T_DOC_COMMENT == $t)
	) $token .= stripPHPWhiteSpaceNComments($source[$i][1]);

	return $token;
}

function runPreprocessor($source, $cache, $level, $class = false)
{
	$file = realpath($source);

	$source = file_get_contents($source);
	if (false !== strpos($source, "\r")) $source = str_replace(array("\r\n", "\r"), array("\n", "\n"), $source);

	if (DEBUG)
	{
		$source = preg_replace("'^#>([^>].*)$'m", '$1', $source);
	}
	else
	{
		$source = preg_replace("'^#>>>\s*^.*^#<<<\s*$'mse", 'preg_replace("/[^\r\n]+/", "", "$0")', $source);
	}

	$tmp = './' . md5(uniqid(mt_rand(), true));
	$h = fopen($tmp, 'wb');

	$source = token_get_all($source);
	$sourceLen = count($source);

	$curly_level = 0;
	$class_pool = array();

	for ($i = 0; $i < $sourceLen; ++$i)
	{
		$token = $source[$i];

		if (is_array($token)) switch ($token[0])
		{
			case T_OPEN_TAG:
				$token = '<?php ' . str_repeat("\n", substr_count($token[1], "\n"));
				break;

			case T_OPEN_TAG_WITH_ECHO == $token[0]:
				$token = '<?php echo ' . str_repeat("\n", substr_count($token[1], "\n"));
				break;

			case T_CLOSE_TAG:
				$token = str_repeat("\n", substr_count($token[1], "\n")) . '?>';
				break;

			case T_CURLY_OPEN:
			case T_DOLLAR_OPEN_CURLY_BRACES:
				$token = $token[1];
				++$curly_level;
				break;

			case T_CLASS:
				// Look backward for the "final" keyword
				$j = 0;
				do $t = isset($source[$i - (++$j)]) && is_array($source[$i-$j]) ? $source[$i-$j][0] : false;
				while (T_COMMENT == $t || T_WHITESPACE == $t);

				$final = isset($source[$i-$j]) && is_array($source[$i-$j]) && T_FINAL == $source[$i-$j][0];


				$c = '';
				$token = $token[1];

				// Look forward
				$j = 0;
				do $t = isset($source[$i + (++$j)]) && is_array($source[$i+$j]) ? $source[$i+$j][0] : false;
				while (T_COMMENT == $t || T_WHITESPACE == $t);

				if (isset($source[$i+$j]) && is_array($source[$i+$j]) && T_STRING == $source[$i+$j][0])
				{
					$token .= fetchPHPWhiteSpaceNComments($source, $i);

					$c = $source[$i][1];

					if ($final) $token .= $c;
					else
					{
						$c = preg_replace("'__[0-9]+$'", '', $c);
						$token .= $c . '__' . $level;
					}

					$token .= fetchPHPWhiteSpaceNComments($source, $i);
				}

				if (!$c)
				{
					if ($class)
					{
						$c = $class;
						$token .= ' ' . $c . (!$final ? '__' . $level : '');
					}

					$token .= fetchPHPWhiteSpaceNComments($source, $i);
				}

				$class_pool[$curly_level] = $c;

				if ($c && isset($source[$i]) && is_array($source[$i]) && T_EXTENDS == $source[$i][0])
				{
					$token .= $source[$i][1];
					$token .= fetchPHPWhiteSpaceNComments($source, $i);
					$token .= isset($source[$i]) && is_array($source[$i]) && 'self' == $source[$i][1] ? $class . '__' . ($level && $c == $class ? $level-1 : $level) : $source[$i][1];
				}
				else --$i;

				break;

			case T_STRING:
				switch ($token[1])
				{
					case '__CIA_LEVEL__':
						$token = $level;
						break;

					case '__CIA_FILE__':
						$token = "'" . str_replace(array('\\', "'"), array('\\\\', "\\'"), $file) . "'";
						break;

					case 'resolvePath':
					case 'processPath':
						$token = $token[1] . fetchPHPWhiteSpaceNComments($source, $i);

						if ('(' == $source[$i])
						{
							$token .= '(';
							$bracket_level = 1;
							$param_position = 0;

							do
							{
								$token .= fetchPHPWhiteSpaceNComments($source, $i);

								if (is_array($source[$i])) $token .= $source[$i][1];
								else
								{
									$token .= $source[$i];

									if (1 == $bracket_level && ',' == $source[$i]) ++$param_position;
									else if ('(' == $source[$i]) ++$bracket_level;
									else if (')' == $source[$i])
									{
										--$bracket_level;

										if (!$bracket_level)
										{
											if (1 == $param_position) $token = substr($token, 0, -1) . ',' . $level . ')';

											break;
										}
									}
								}
							}
							while (1);
						}
						else --$i;

						break;

					case 'self':
						if ($class_pool)
						{
							$token = fetchPHPWhiteSpaceNComments($source, $i);
							$token = (T_DOUBLE_COLON == $source[$i][0] ? end($class_pool) : 'self') . $token;

							--$i;

							break;
						}

					default:
						$token = $token[1];
						break;
				}

				break;

			case T_COMMENT:
			case T_WHITESPACE:
			case T_DOC_COMMENT:
				$token = stripPHPWhiteSpaceNComments($token[1]);
				break;

			default:
				$token = $token[1];
				break;
		}
		else if ('{' == $token) ++$curly_level;
		else if ('}' == $token) unset($class_pool[$curly_level--]);

		fwrite($h, $token, strlen($token));
	}

	$token =& $source[$sourceLen - 1];

	if (!is_array($token) || (T_CLOSE_TAG != $token[0] && T_INLINE_HTML != $token[0])) fwrite($h, '?>', 2);

	fclose($h);

	if (CIA_WINDOWS)
	{
		$h = new COM('Scripting.FileSystemObject');
		$h->GetFile($GLOBALS['cia_paths'][0] . '/' . $tmp)->Attributes |= 2;
		file_exists($cache) && unlink($cache);
	}

	rename($tmp, $cache);
}

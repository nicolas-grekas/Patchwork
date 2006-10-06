<?php

extension_loaded('tokenizer') || die('Extension "tokenizer" is needed and not loaded.');
extension_loaded('Reflection') || die('Extension "Reflection" is needed and not loaded.');


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

	while (($t = @$source[++$i][0]) && (T_COMMENT == $t || T_WHITESPACE == $t || T_DOC_COMMENT == $t)) $token .= stripPHPWhiteSpaceNComments($source[$i][1]);

	return $token;
}

function runPreprocessor($source, $cache, $level, $class = false)
{
	$file = $GLOBALS['cia_paths'];
	$file = substr($source, strlen($file[count($file) - $level - 1]) + 1);

	$source = file_get_contents($source);
	$source = str_replace(array("\r\n", "\r"), array("\n", "\n"), $source);

	if (DEBUG)
	{
		$source = preg_replace("'^#>([^>].*)$'m", '$1', $source);
	}
	else
	{
		$source = preg_replace("'^#>>>\s*^.*^#<<<\s*$'mse", 'preg_replace("/[^\r\n]+/", "", "$0")', $source);
	}

	$tmp = md5(uniqid(mt_rand(), true));
	$h = fopen($tmp, 'wb');

	$source = token_get_all($source);
	$sourceLen = count($source);

	$curly_level = 0;
	$class_pool = array();

	for ($i = 0; $i < $sourceLen; ++$i)
	{
		$token = $source[$i];

		if (is_array($token))
		{
			if (T_CURLY_OPEN == $token[0] || T_DOLLAR_OPEN_CURLY_BRACES == $token[0])
			{
				$token = $token[1];
				++$curly_level;
			}
			else if (T_CLASS == $token[0])
			{
				// Look backward for the "final" keyword
				$j = 0;
				do $t = @$source[$i - (++$j)][0];
				while (T_COMMENT == $t || T_WHITESPACE == $t);

				$final = T_FINAL == @$source[$i-$j][0];


				$c = '';
				$token = $token[1];

				// Look forward
				$j = 0;
				do $t = @$source[$i + (++$j)][0];
				while (T_COMMENT == $t || T_WHITESPACE == $t);

				if (T_STRING == @$source[$i+$j][0])
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

				if ($c && T_EXTENDS == @$source[$i][0])
				{
					$token .= $source[$i][1];
					$token .= fetchPHPWhiteSpaceNComments($source, $i);
					$token .= 'self' == @$source[$i][1] ? $class . '__' . ($level && $c == $class ? $level-1 : $level) : $source[$i][1];
				}
				else --$i;
			}
			else if (T_STRING == $token[0] && 'self' == $token[1] && $class_pool)
			{
				$token = fetchPHPWhiteSpaceNComments($source, $i);
				$token = (T_DOUBLE_COLON == $source[$i][0] ? end($class_pool) : 'self') . $token;

				--$i;
			}
			else if (T_STRING == $token[0] && '__CIA_LEVEL__' == $token[1])
			{
				$token = $level;
			}
			else if (T_STRING == $token[0] && '__CIA_FILE__' == $token[1])
			{
				$token = "'" . str_replace(array('\\', "'"), array('\\\\', "\\'"), $file) . "'";
			}
			else if (T_STRING == $token[0] && ('resolvePath' == $token[1] || 'processPath' == $token[1]))
			{
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
			}
			else
			{
				$token = T_COMMENT == $token[0] || T_WHITESPACE == $token[0] || T_DOC_COMMENT == $token[0]
					? stripPHPWhiteSpaceNComments($token[1])
					: $token[1];
			}
		}
		else if ('{' == $token) ++$curly_level;
		else if ('}' == $token) unset($class_pool[$curly_level--]);

		fwrite($h, $token, strlen($token));
	}

	$token =& $source[$sourceLen - 1];

	if (!is_array($token) || (T_CLOSE_TAG != $token[0] && T_INLINE_HTML != $token[0])) fwrite($h, '?>', 2);

	fclose($h);

	if ('WIN' == substr(PHP_OS, 0, 3)) 
	{
		$h = new COM('Scripting.FileSystemObject');
		$h->GetFile($GLOBALS['cia_paths'][0] . '/' . $tmp)->Attributes |= 2;
		file_exists($cache) && unlink($cache);
	}

	rename($tmp, $cache);
}

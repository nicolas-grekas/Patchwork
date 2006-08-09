<?php

if (!function_exists('stripPHPWhiteSpaceNComments'))
{
	function stripPHPWhiteSpaceNComments($a)
	{
		$a = preg_replace(
			array("'[^\r\n]+'", "' +([\r\n])'", "'([\r\n]) +'"),
			array(' '         , '$1'          , '$1'          ),
			$a
		);

		return $a;
	}

	function fetchPHPWhiteSpaceNComments(&$tokens, &$i)
	{
		$token = '';

		while (($t = @$tokens[++$i][0]) && (T_COMMENT == $t || T_WHITESPACE == $t || T_DOC_COMMENT == $t)) $token .= stripPHPWhiteSpaceNComments($tokens[$i][1]);

		return $token;
	}
}

extension_loaded('tokenizer') || die('Extension "tokenizer" is needed and not loaded.');
extension_loaded('Reflection') || die('Extension "Reflection" is needed and not loaded.');


$tmp = md5(uniqid(mt_rand(), true) . '.php');

$h = fopen($tmp, 'wb');

$tokens = token_get_all(file_get_contents($file));
$tokensLen = count($tokens);

$curly_level = 0;
$class_pool = array();

for ($i = 0; $i < $tokensLen; ++$i)
{
	$token = $tokens[$i];

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
			do $t = @$tokens[$i - (++$j)][0];
			while (T_COMMENT == $t || T_WHITESPACE == $t);

			$final = T_FINAL == @$tokens[$i-$j][0];


			$c = '';
			$token = $token[1];

			// Look forward
			$j = 0;
			do $t = @$tokens[$i + (++$j)][0];
			while (T_COMMENT == $t || T_WHITESPACE == $t);

			if (T_STRING == @$tokens[$i+$j][0])
			{
				$token .= fetchPHPWhiteSpaceNComments($tokens, $i);

				$c = $tokens[$i][1];

				if ($final) $token .= $c;
				else
				{
					$c = preg_replace("'__[0-9]+$'", '', $c);
					$token .= $c . '__' . $level;
				}

				$token .= fetchPHPWhiteSpaceNComments($tokens, $i);
			}

			if (!$c)
			{
				$c = $class;
				$token .= ' ' . $c . (!$final ? '__' . $level : '');
				$token .= fetchPHPWhiteSpaceNComments($tokens, $i);
			}

			$class_pool[$curly_level] = $c;

			if (T_EXTENDS == @$tokens[$i][0])
			{
				$token .= $tokens[$i][1];
				$token .= fetchPHPWhiteSpaceNComments($tokens, $i);
				$token .= 'self' == @$tokens[$i][1] ? $class . '__' . ($level && $c == $class ? $level-1 : $level) : $tokens[$i][1];
			}
			else --$i;
		}
		else if (T_STRING == $token[0] && 'self' == $token[1])
		{
			$token = fetchPHPWhiteSpaceNComments($tokens, $i);
			$token = (T_DOUBLE_COLON == $tokens[$i][0] ? end($class_pool) : 'self') . $token;

			--$i;
		}
		else $token = (T_COMMENT == $token[0] || T_WHITESPACE == $token[0] || T_DOC_COMMENT == $token[0])
			? stripPHPWhiteSpaceNComments($token[1])
			: $token[1];
	}
	else if ('{' == $token) ++$curly_level;
	else if ('}' == $token) unset($class_pool[$curly_level--]);

	fwrite($h, $token);
}

fclose($h);


if ('WIN' == substr(PHP_OS, 0, 3))
{
	$h = new COM('Scripting.FileSystemObject');
	$h->GetFile($paths[0] . '/' . $tmp)->Attributes |= 2;
	$h = @unlink($path);
}

rename($tmp, $path);

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

	function fetchPHPWhiteSpaceNComments(&$source, &$i)
	{
		$token = '';

		while (($t = @$source[++$i][0]) && (T_COMMENT == $t || T_WHITESPACE == $t || T_DOC_COMMENT == $t)) $token .= stripPHPWhiteSpaceNComments($source[$i][1]);

		return $token;
	}
}

extension_loaded('tokenizer') || die('Extension "tokenizer" is needed and not loaded.');
extension_loaded('Reflection') || die('Extension "Reflection" is needed and not loaded.');


$cacheBackup = $cache;
$cache = md5(uniqid(mt_rand(), true));

require resolvePath('preprocessor.php');

$source = file_get_contents($cache);
unlink($cache);
$cache = $cacheBackup;

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
				$c = $class;
				$token .= ' ' . $c . (!$final ? '__' . $level : '');
				$token .= fetchPHPWhiteSpaceNComments($source, $i);
			}

			$class_pool[$curly_level] = $c;

			if (T_EXTENDS == @$source[$i][0])
			{
				$token .= $source[$i][1];
				$token .= fetchPHPWhiteSpaceNComments($source, $i);
				$token .= 'self' == @$source[$i][1] ? $class . '__' . ($level && $c == $class ? $level-1 : $level) : $source[$i][1];
			}
			else --$i;
		}
		else if (T_STRING == $token[0] && 'self' == $token[1])
		{
			$token = fetchPHPWhiteSpaceNComments($source, $i);
			$token = (T_DOUBLE_COLON == $source[$i][0] ? end($class_pool) : 'self') . $token;

			--$i;
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

	fwrite($h, $token);
}

fclose($h);

if ('WIN' == substr(PHP_OS, 0, 3)) 
{
	$h = new COM('Scripting.FileSystemObject');
	$h->GetFile($paths[0] . '/' . $tmp)->Attributes |= 2;
	$h = @unlink($cache);
}

rename($tmp, $cache);

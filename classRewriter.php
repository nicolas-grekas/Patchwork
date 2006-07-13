<?php // vim: set noet ts=4 sw=4 fdm=marker:

$abstract = false;
$final = false;

$tmp = dirname($path);
if (!file_exists($tmp))
{
	mkdir($tmp);

	if ('WIN' == substr(PHP_OS, 0, 3))
	{
		$h = new COM('Scripting.FileSystemObject');
		$h->GetFolder($tmp)->Attributes |= 2;
	}
}

extension_loaded('tokenizer') || die('Extension "tokenizer" is needed and not loaded.');
extension_loaded('Reflection') || die('Extension "Reflection" is needed and not loaded.');

$tmp .= '/' . md5(uniqid(mt_rand(), true) . '.php');

$h = fopen($tmp, 'wb');

$tokens = token_get_all(file_get_contents($file));
$tokensLen = count($tokens);

for ($i = 0; $i < $tokensLen; ++$i)
{
	$token = $tokens[$i];

	if (is_array($token))
	{
		if (T_CLASS == $token[0])
		{
			$a = T_ABSTRACT == @$tokens[$i-2][0];
			$f = T_FINAL == @$tokens[$i-2][0];

			$c = '';
			$token = $token[1];

			if (T_STRING == @$tokens[$i+2][0])
			{
				$token .= $tokens[++$i][1];
				$c = preg_replace("'__[0-9]+$'", '', $tokens[++$i][1]);
				$token .= $c . '__' . $level;
				if (T_WHITESPACE == @$tokens[$i+1][0]) $token .= $tokens[++$i][1];
			}

			if (!$c)
			{
				$c = $class;
				$token .= ' ' . $c . '__' . $level . $tokens[++$i][1];
			}

			if ($c == $class)
			{
				if ($a) $abstract = true;
				else if ($f) $final = true;
			}

			if (T_EXTENDS == @$tokens[$i+1][0])
			{
				$token .= $tokens[++$i][1] . $tokens[++$i][1];

				++$i;

				$token .= 'parent' == @$tokens[$i][1] ? $class . '__' . ($level && $c == $class ? $level-1 : $level) : $tokens[$i][1];
			}
		}
		else $token = $token[1];
	}

	fwrite($h, $token);
}

fclose($h);

$filesource = $path . ($abstract ? 'a' : ($final ? 'f' : 'c')) . '.php';

if ('WIN' == substr(PHP_OS, 0, 3)) @unlink($filesource);
rename($tmp, $filesource);

if ($abstract || $final) @unlink($path . 'c.php');
if (!$abstract) @unlink($path . 'a.php');
if (!$final) @unlink($path . 'f.php');

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
defined('T_GOTO')         || define('T_GOTO',         -1);
defined('T_USE' )         || define('T_USE' ,         -1);
defined('T_DIR' )         || define('T_DIR' ,         -1);
defined('T_NS_C')         || define('T_NS_C',         -1);
defined('T_NAMESPACE')    || define('T_NAMESPACE',    -1);
defined('T_NS_SEPARATOR') || define('T_NS_SEPARATOR', -1);

// New token to match T_CURLY_OPEN and T_DOLLAR_OPEN_CURLY_BRACES
define('T_CURLY_CLOSE', -2);

class patchwork_tokenizer
{
	public $tokens;

	protected

	$code,
	$position,
	$registry = array();


	function register($object, $method)
	{
		foreach ($method as $method => $token)
			foreach ((array) $token as $token)
				$this->registry[$token][] = array($object, $method);
	}

	function unregister($object, $method)
	{
		foreach ($method as $method => $token)
			foreach ((array) $token as $token)
				if (isset($this->registry[$token]))
					foreach ($this->registry[$token] as $k => $v)
						if ($v[0] === $object && 0 === strcasecmp($v[1], $method))
							unset($this->registry[$token][$k]);
	}

	function tokenize($code, $strip = true)
	{
		$registry =& $this->registry;

		if ('' === $code) return $code;

		$code   = $this->getTokens($code);
		$i      = 0;
		$tokens = array();

		$this->code     =& $code;
		$this->position =& $i;
		$this->tokens   =& $tokens;

		$length   = count($code);
		$line     = 1;
		$inString = 0;
		$deco     = '';

		while ($i < $length)
		{
			$lines = 0;

			if (is_array($code[$i]))
			{
				$token = $code[$i];

				if (isset($token[2])) $line = $token[2];
				else $token[2] = $line;

				switch ($token[0])
				{
				case T_OPEN_TAG:
				case T_CLOSE_TAG:
				case T_INLINE_HTML:
				case T_OPEN_TAG_WITH_ECHO:
				case T_CONSTANT_ENCAPSED_STRING:
				case T_ENCAPSED_AND_WHITESPACE:
					$lines = substr_count($token[1], "\n");
					break;

				case T_CURLY_OPEN:
				case T_DOLLAR_OPEN_CURLY_BRACES:
				case T_START_HEREDOC: ++$inString; break;
				case T_END_HEREDOC:   --$inString; break;
				case T_STRING:
					if ($inString & 1) $token[0] = T_ENCAPSED_AND_WHITESPACE;
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
						// FIXME: This can be broken with a closure definition
						//   inside an interpolated string. Not very common...
						--$inString;
						$token = array(T_CURLY_CLOSE, '}', $line);
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

			'' !== $deco && $token[3] = $deco;

			if (isset($registry[$token[0]]))
			{
				foreach ($registry[$token[0]] as $t)
				{
					$t[0]->{$t[1]}($token, $this);
					if (!$token) continue 2;
				}
			}

			$tokens[] = $token;
			$lines += $lines;
			$deco = '';

			while ($i < $length && (
				   T_WHITESPACE  === $code[$i][0]
				|| T_COMMENT     === $code[$i][0]
				|| T_DOC_COMMENT === $code[$i][0]
			))
			{
				$token = $code[$i];
				unset($code[$i++]);

				$lines = substr_count($token[1], "\n");

				if (isset($registry[$token[0]]))
				{
					$strip && $token[3] = $deco;

					foreach ($registry[$token[0]] as $t)
					{
						$t[0]->{$t[1]}($token, $this);
						if (!$token) continue 2;
					}
				}

				if ($strip)
				{
					if (T_DOC_COMMENT !== $token[0])
					{
						$token[0] = T_WHITESPACE;
						$token[1] = $lines ? str_repeat("\n", $lines) : ' ';
					}

					$deco .= $token[1];
				}
				else $tokens[] = $token;

				$line += $lines;
			}
		}

		unset($this->tokens);
		$this->tokens = array();

		return $tokens;
	}

	protected function getTokens($code)
	{
		return token_get_all($code);
	}

	protected static $variableType = array(
		T_EVAL, '(', T_LINE, T_FILE, T_DIR, T_FUNC_C, T_CLASS_C,
		T_METHOD_C, T_NS_C, T_INCLUDE, T_REQUIRE, T_GOTO,
		T_CURLY_OPEN, T_VARIABLE, '$', T_INCLUDE_ONCE,
		T_REQUIRE_ONCE, T_DOLLAR_OPEN_CURLY_BRACES, T_EXIT,
	);

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

			case T_WHITESPACE: break;

			default:
				if (in_array($type, $variableType, true)) $close = 2;
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
			else $new_code[] = $deco . $code;
		}
	}

	static function export($a, $lf = 0)
	{
		if (is_array($a))
		{
			if ($a)
			{
				$i = 0;
				$b = array();

				foreach ($a as $k => &$a)
				{
					if (is_int($k) && $k >= 0)
					{
						$b[] = ($k !== $i ? $k . '=>' : '') . self::export($a);
						$i = $k+1;
					}
					else
					{
						$b[] = self::export($k) . '=>' . self::export($a);
					}
				}

				$b = 'array(' . implode(',', $b) . ')';
			}
			else return 'array()';
		}
		else if (is_object($a))
		{
			$b = array();
			$v = (array) $a;
			foreach ($v as $k => &$v)
			{
				if ("\0" === substr($k, 0, 1)) $k = substr($k, 3);
				$b[$k] =& $v;
			}

			$b = self::export($b);
			$b = get_class($a) . '::__set_state(' . $b . ')';
		}
		else if (is_string($a))
		{
			if ($a !== strtr($a, "\r\n\0", '---'))
			{
				$b = '"'. str_replace(
					array(  "\\",   '"',   '$',  "\r",  "\n",  "\0"),
					array('\\\\', '\\"', '\\$', '\\r', '\\n', '\\0'),
					$a
				) . '"';
			}
			else
			{
				$b = "'" . str_replace(
					array('\\', "'"),
					array('\\\\', "\\'"),
					$a
				) . "'";
			}
		}
		else if (is_bool($a))
		{
			$b = $a ? 'true' : 'false';
		}
		else $b = is_null($a) ? 'null' : (string) $a;

		$lf && $b .= str_repeat("\n", $lf);

		return $b;
	}
}

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
	public $tokens;

	protected

	$code,
	$position,
	$tokenRegistry = array(),
	$callbackRegistry = array();


	function register($object, $method)
	{
		if (is_array($method))
			foreach ($method as $method => $token)
				foreach ((array) $token as $token)
					$this->tokenRegistry[$token][] = array($object, $method);
		else
			$this->callbackRegistry[] = array($object, $method);
	}

	function unregister($object, $method)
	{
		if (is_array($method))
			foreach ($method as $method => $token)
				foreach ((array) $token as $token)
					if (isset($this->tokenRegistry[$token]))
						foreach ($this->tokenRegistry[$token] as $k => $v)
							if ($v[0] === $object && 0 === strcasecmp($v[1], $method))
								unset($this->tokenRegistry[$token][$k]);
		else
			foreach ($this->callbackRegistry as $k => $v)
				if ($v[0] === $object && 0 === strcasecmp($v[1], $method))
					unset($this->callbackRegistry[$k]);
	}

	function tokenize($code, $strip = true)
	{
		$tRegistry =& $this->tokenRegistry;
		$cRegistry =& $this->callbackRegistry;

		$code   = token_get_all($code);
		$i      = 0;
		$tokens = array();

		$this->code     =& $code;
		$this->position =& $i;
		$this->tokens   =& $tokens;

		$length   = count($code);
		$line     = 1;
		$inString = 0;
		$deco     = '';

		if (!$length) return $tokens;

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

			'' !== $deco && $token[3] = $deco;

			if (isset($tRegistry[$token[0]]))
			{
				foreach ($tRegistry[$token[0]] as $t)
				{
					$t[0]->{$t[1]}($token, $this);
					if (!$token) continue 2;
				}
			}

			foreach ($cRegistry as $t)
			{
				$t[0]->{$t[1]}($token, $this);
				if (!$token) continue 2;
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

				if (isset($tRegistry[$token[0]]))
				{
					$strip && $token[3] = $deco;

					foreach ($tRegistry[$token[0]] as $t)
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
				else
				{
					foreach ($cRegistry as $t)
					{
						$t[0]->{$t[1]}($token, $this);
						if (!$token) continue 2;
					}

					$tokens[] = $token;
				}

				$line += $lines;
			}
		}

		unset($this->tokens);
		$this->tokens = array();

		return $tokens;
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
}

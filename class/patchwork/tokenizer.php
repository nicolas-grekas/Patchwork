<?php /*********************************************************************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


patchwork_tokenizer::createToken('T_CURLY_CLOSE');     // closing braces opened with T_CURLY_OPEN or T_DOLLAR_OPEN_CURLY_BRACES
patchwork_tokenizer::createToken('T_COMPILER_HALTED'); // data after T_HALT_COMPILER


class patchwork_tokenizer
{
	protected

	$dependencyName = null,
	$dependencies = array(),

	$inString = 0,
	$line   = 0,
	$index  = 0,
	$tokens = array(),
	$types  = array(),
	$codes  = array(),
	$prevType,
	$anteType,
	$tokenRegistry    = array(),
	$catchallRegistry = array();


	private

	$parents = array(),
	$errors  = array(),
	$nextRegistryIndex = 0,

	$registryIndex     = 0;


	protected static

	$sugar = array(
		T_WHITESPACE  => 1,
		T_COMMENT     => 1,
		T_DOC_COMMENT => 1,
	);

	private static $tokenNames = array();


	function __construct(self $parent = null)
	{
		$this->dependencyName || $this->dependencyName = get_class($this);
		$this->dependencies = (array) $this->dependencies;

		if ($parent)
		{
			$v = array(
				'line',
				'tokens',
				'index',
				'types',
				'texts',
				'prevType',
				'anteType',
				'tokenRegistry',
				'catchallRegistry',
				'parents',
				'errors',
				'nextRegistryIndex',
			);

			foreach ($v as $v) $this->$v =& $parent->$v;
		}
		else $parent = $this;

		foreach ($this->dependencies as $k => $v)
		{
			unset($this->dependencies[$k]);

			if (is_string($k))
			{
				$c = (array) $v;
				$v = $k;
			}
			else $c = array();

			$k = strtolower('\\' !== $v[0] ? __CLASS__ . '_' . $v : substr($v, 1));

			if (!isset($this->parents[$k]))
			{
				return trigger_error(get_class($this) . ' failed dependency: ' . $v);
			}

			$this->dependencies[$v] = $this->parents[$k];

			foreach ($c as $c) $this->$c =& $this->parents[$k]->$c;
		}

		$k = strtolower($this->dependencyName);
		$this->parents[$k] = $this;

		$this->nextRegistryIndex += 1 << (PHP_INT_SIZE << 2) >> 1;
		$this->registryIndex = $this->nextRegistryIndex;

		empty($this->callbacks) || $this->register();
	}

	function getErrors()
	{
		return $this->errors;
	}

	function parse($code)
	{
		if ('' === $code) return array();

		$this->tokens = $this->getTokens($code);
		unset($code);

		$inString =& $this->inString; $inString = 0;
		$line     =& $this->line;     $line     = 1;
		$i        =& $this->index;    $i        = 0;
		$types    =& $this->types;    $types    = array();
		$texts    =& $this->texts;    $texts    = array('');
		$prevType =& $this->prevType; $prevType = false;
		$anteType =& $this->anteType; $anteType = false;
		$tokens   =& $this->tokens;
		$tkReg    =& $this->tokenRegistry;
		$caReg    =& $this->catchallRegistry;

		$j         = 0;
		$curly     = 0;
		$curlyPool = array();

		while (isset($tokens[$i]))
		{
			$t =& $tokens[$i];
			unset($tokens[$i++]);

			$sugar = 0;

			if (isset($t[1]))
			{
				if ($inString & 1) switch ($t[0])
				{
				case T_VARIABLE:
				case T_CURLY_OPEN:
				case T_END_HEREDOC:
				case T_DOLLAR_OPEN_CURLY_BRACES: break;
				case T_STRING:
				case T_NUM_STRING: if ('[' === $prevType) break;
				case T_OBJECT_OPERATOR: if (T_VARIABLE === $prevType) break;
				default: $t[0] = T_ENCAPSED_AND_WHITESPACE;
				}
				else switch ($t[0])
				{
				case T_WHITESPACE:
				case T_COMMENT:
				case T_DOC_COMMENT: $sugar = 1;
				}
			}
			else
			{
				$t = array($t, $t);

				if ($inString & 1) switch ($t[0])
				{
				case '"':
				case '`': break;
				case ']': if (T_STRING   === $prevType || T_NUM_STRING === $prevType) break;
				case '[': if (T_VARIABLE === $prevType && '[' === $t[0]) break;
				default: $t[0] = T_ENCAPSED_AND_WHITESPACE;
				}
				else if ('}' === $t[0] && !$curly) $t[0] = T_CURLY_CLOSE;
			}

			if (isset($tkReg[$t[0]]) || ($caReg && !$sugar))
			{
				$t[2] = array();
				$k = $t[0];
				$callbacks = $sugar ? array() : $caReg;

				do
				{
					$t[2][$k] = 1;

					if (isset($tkReg[$k]))
					{
						$callbacks += $tkReg[$k];
						ksort($callbacks);
					}

					foreach ($callbacks as $k => $c)
					{
						unset($callbacks[$k]);

						if (false === $k = $c[0]->$c[1]($t)) continue 3;
						else if (null !== $k && empty($t[2][$k])) continue 2;
					}

					break;
				}
				while (1);
			}

			$texts[++$j] =& $t[1];

			if ($sugar)
			{
				$line += substr_count($t[1], "\n");
				continue;
			}

			$anteType  = $prevType;
			$types[$j] = $prevType = $t[0];

			if (isset($prevType[0])) switch ($prevType)
			{
			case '{': ++$curly; break;
			case '}': --$curly; break;
			case '"':
			case '`': $inString += ($inString & 1) ? -1 : 1;
			}
			else switch ($prevType)
			{
			case T_CONSTANT_ENCAPSED_STRING:
			case T_ENCAPSED_AND_WHITESPACE:
			case T_OPEN_TAG_WITH_ECHO:
			case T_INLINE_HTML:
			case T_CLOSE_TAG:
			case T_OPEN_TAG:
				$line += substr_count($t[1], "\n");
				break;

			case T_DOLLAR_OPEN_CURLY_BRACES:
			case T_CURLY_OPEN:    $curlyPool[] = $curly; $curly = 0;
			case T_START_HEREDOC: ++$inString; break;

			case T_CURLY_CLOSE:   $curly = array_pop($curlyPool);
			case T_END_HEREDOC:   --$inString; break;

			case T_HALT_COMPILER:
				$sugar = 2;
				$curly = $i;

				// Skip 3 tokens: "(", ")" then ";" or T_CLOSE_TAG
				do while (isset($tokens[$i], self::$sugar[$tokens[$i][0]])) ++$i;
				while ($sugar-- > 0 && ++$i);

				$sugar = $i + 1;
				$curlyPool = array();

				// Everything after is merged into one T_COMPILER_HALTED
				while (isset($tokens[++$i]))
				{
					$curlyPool[] = isset($tokens[$i][1]) ? $tokens[$i][1] : $tokens[$i];
					unset($tokens[$i]);
				}

				$curlyPool && $tokens[$sugar] = array(T_COMPILER_HALTED, implode('', $curlyPool));

				$i = $curly;
				$curly = 0;
				$curlyPool = array();
				break;
			}
		}

		// Free memory thanks to copy-on-write
		$j     = $texts;
		$types = $texts = $tokens = $tkReg = $caReg = $this->parents = array();
		$line = 0;

		return $j;
	}

	protected function setError($message, $type = E_USER_ERROR)
	{
		$this->errors[] = array($message, (int) $this->line, get_class($this), $type);
	}

	protected function register($method = null)
	{
		null === $method && $method = $this->callbacks;

		foreach ((array) $method as $method => $type)
		{
			if (is_int($method))
			{
				isset($sort) || $sort = 1;
				$this->catchallRegistry[++$this->registryIndex] = array($this, $type);
			}
			else foreach ((array) $type as $type)
			{
				$this->tokenRegistry[$type][++$this->registryIndex] = array($this, $method);
			}
		}

		isset($sort) && ksort($this->catchallRegistry);
	}

	protected function unregister($method = null)
	{
		null === $method && $method = $this->callbacks;

		foreach ((array) $method as $method => $type)
		{
			if (is_int($method))
			{
				foreach ($this->catchallRegistry as $k => $v)
					if (array($this, $type) === $v)
						unset($this->catchallRegistry[$k]);
			}
			else foreach ((array) $type as $type)
			{
				if (isset($this->tokenRegistry[$type]))
				{
					foreach ($this->tokenRegistry[$type] as $k => $v)
						if (array($this, $method) === $v)
							unset($this->tokenRegistry[$type][$k]);

					if (!$this->tokenRegistry[$type]) unset($this->tokenRegistry[$type]);
				}
			}
		}
	}

	protected function &getNextToken($skip = 0)
	{
		$i = $this->index;

		do while (isset($this->tokens[$i], self::$sugar[$this->tokens[$i][0]])) ++$i;
		while ($skip-- > 0 && ++$i);

		isset($this->tokens[$i]) || $this->tokens[$i] = array(T_WHITESPACE, '');

		return $this->tokens[$i];
	}

	protected function tokensUnshift()
	{
		foreach (func_get_args() as $token)
			$this->tokens[--$this->index] = $token;

		return false;
	}

	protected function getTokens($code)
	{
		return token_get_all($code);
	}

	static function createToken($name)
	{
		static $type = 0;
		define($name, --$type);
		self::$tokenNames[$type] = $name;
	}

	static function getTokenName($type)
	{
		if (is_string($type)) return $type;
		return isset(self::$tokenNames[$type]) ? self::$tokenNames[$type] : token_name($type);
	}

	static function export($a)
	{
		switch (true)
		{
		case is_array($a):
			$i = 0;
			$b = array();

			foreach ($a as $k => $a)
			{
				if (is_int($k) && 0 <= $k)
				{
					$b[] = ($k !== $i ? $k . '=>' : '') . self::export($a);
					$i = $k + 1;
				}
				else
				{
					$b[] = self::export($k) . '=>' . self::export($a);
				}
			}

			return 'array(' . implode(',', $b) . ')';

		case is_object($a):
			return 'unserialize(' . self::export(serialize($a)) . ')';

		case is_string($a):
			if ($a !== strtr($a, "\r\n\0", '---'))
			{
				return '"'. str_replace(
					array(  "\\",   '"',   '$',  "\r",  "\n",  "\0"),
					array('\\\\', '\\"', '\\$', '\\r', '\\n', '\\0'),
					$a
				) . '"';
			}

			return "'" . str_replace(
				array(  '\\',   "'"),
				array('\\\\', "\\'"),
				$a
			) . "'";

		case true  === $a: return 'true';
		case false === $a: return 'false';
		case null  === $a: return 'null';
		case INF   === $a: return 'INF';
		case NAN   === $a: return 'NAN';
		default:           return (string) $a;
		}
	}
}

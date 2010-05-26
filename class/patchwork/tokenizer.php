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
defined('T_GOTO')         || patchwork_tokenizer::defineNewToken('T_GOTO');
defined('T_USE' )         || patchwork_tokenizer::defineNewToken('T_USE');
defined('T_DIR' )         || patchwork_tokenizer::defineNewToken('T_DIR');
defined('T_NS_C')         || patchwork_tokenizer::defineNewToken('T_NS_C');
defined('T_NAMESPACE')    || patchwork_tokenizer::defineNewToken('T_NAMESPACE');
defined('T_NS_SEPARATOR') || patchwork_tokenizer::defineNewToken('T_NS_SEPARATOR');

// Match closing braces opened with T_CURLY_OPEN or T_DOLLAR_OPEN_CURLY_BRACES
patchwork_tokenizer::defineNewToken('T_CURLY_CLOSE');

// Sub-token for multilines T_WHITESPACE, T_COMMENT and T_DOC_COMMENT
patchwork_tokenizer::defineNewToken('T_MULTILINE');

class patchwork_tokenizer
{
	protected

	$line = 0,
	$code,
	$position,
	$tokens,
	$prevType,
	$anteType,

	$tokenRegistry    = array(),
	$callbackRegistry = array(),
	$parent,
	$shared = array(
		'line',
		'code',
		'position',
		'tokens',
		'prevType',
		'anteType',
		'tokenizerError',
		'registryPosition',
		'positionRegistry',
		'tokenRegistry',
		'callbackRegistry',
	);


	private

	$tokenizerError = false,
	$tokenizerOrder,
	$registryPosition = 0,
	$positionRegistry = array(0);


	function __construct(self $parent = null)
	{
		$parent || $parent = $this;
		$this->initialize($parent);
	}

	protected function initialize(self $parent)
	{
		$this->parent = $parent;

		if ($this instanceof $parent || is_subclass_of($parent, get_parent_class($this)))
		{
			if ($this !== $parent)
			{
				foreach ($this->parent->shared as $parent)
					$this->$parent =& $this->parent->$parent;

				$this->shared = array_unique(array_merge((array) $this->shared, $this->parent->shared));
				$this->parent->shared =& $this->shared;
			}

			$this->tokenizerOrder = ++$this->positionRegistry[0];
			$this->positionRegistry[$this->tokenizerOrder] = $this->registryPosition + 100000;

			empty($this->callbacks) || $this->register();
		}
		else
		{
			trigger_error('Argument 1 passed to '
				. get_class($this) . '::initialize() must be an instance of '
				. get_parent_class($this) . ', instance of '
				. get_class($parent) . ' given'
			);
		}
	}

	static function defineNewToken($name)
	{
		static $offset = 0;
		define($name, --$offset);
	}

	protected function register($method = null)
	{
		null === $method && $method = $this->callbacks;

		$this->registryPosition = $this->positionRegistry[$this->tokenizerOrder];

		$sort = array();

		foreach ((array) $method as $method => $token)
		{
			if (is_int($method))
			{
				isset($sort['']) || $sort[''] =& $this->callbackRegistry;
				$this->callbackRegistry[++$this->registryPosition] = array($this, $token, 0);
			}
			else foreach ((array) $token as $s => $token)
			{
				isset($sort[$token]) || $sort[$token] =& $this->tokenRegistry[$token];
				$this->tokenRegistry[$token][++$this->registryPosition] = array($this, $method, $s < 0 ? $s : 0);
			}
		}

		foreach ($sort as &$sort) ksort($sort);

		$this->positionRegistry[$this->tokenizerOrder] = $this->registryPosition;
	}

	protected function unregister($method = null)
	{
		null === $method && $method = $this->callbacks;

		foreach ((array) $method as $method => $token)
		{
			if (is_int($method))
			{
				foreach ($this->callbackRegistry as $k => $v)
					if (array($this, $token, 0) === $v)
						unset($this->callbackRegistry[$k]);
			}
			else foreach ((array) $token as $s => $token)
			{
				if (isset($this->tokenRegistry[$token]))
				{
					foreach ($this->tokenRegistry[$token] as $k => $v)
						if (array($this, $method, $s < 0 ? $s : 0) === $v)
							unset($this->tokenRegistry[$token][$k]);

					if (!$this->tokenRegistry[$token]) unset($this->tokenRegistry[$token]);
				}
			}
		}
	}

	protected function setError($message)
	{
		if (!$this->tokenizerError)
		{
			$this->tokenizerError = array($message, (int) $this->line, get_class($this));
		}
	}

	function getError()
	{
		return $this->tokenizerError;
	}

	function tokenize($code)
	{
		if ($this->parent !== $this) return $this->parent->tokenize($code);

		if ('' === $code) return $code;

		$tRegistry =& $this->tokenRegistry;
		$cRegistry =& $this->callbackRegistry;

		$this->code = $this->getTokens($code);

		$line     =& $this->line;
		$code     =& $this->code;
		$i        =& $this->position;
		$tokens   =& $this->tokens;
		$prevType =& $this->prevType;
		$anteType =& $this->anteType;

		$i        = 0;
		$tokens   = array();
		$prevType = false;
		$anteType = false;

		$line     = 1;
		$curly    = 0;
		$strCurly = array();
		$deco     = '';

		while (isset($code[$i]))
		{
			$lines = 0;
			$token =& $code[$i];
			unset($code[$i++]);

			if (isset($token[1]))
			{
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
					$strCurly[] = $curly;
					$curly = 0;
					break;
				}
			}
			else
			{
				$token = array($token, $token);

				switch ($token[0])
				{
				case '{': ++$curly; break;
				case '}':
					if (0 > --$curly)
					{
						$token[0] = T_CURLY_CLOSE;
						$curly    = array_pop($strCurly);
					}
				}
			}

			if ('' !== $deco) $token[2] = $deco;
			else unset($token[2]);

			if ($cRegistry || isset($tRegistry[$token[0]]))
			{
				if (!$t = $cRegistry)
				{
					$t = $tRegistry[$token[0]];
				}
				else if (isset($tRegistry[$token[0]]))
				{
					$t += $tRegistry[$token[0]];
					ksort($t);
				}

				foreach ($t as $t)
				{
					if (0 === $t[2] || (isset($token[3]) && $token[3] === $t[2]))
					{
						if (false === $t[0]->{$t[1]}($token)) continue 2;
					}
				}
			}

			$tokens[] =& $token;
			$line += $lines;
			$deco = '';

			$anteType = $prevType;
			$prevType = $token[0];

			while (isset($code[$i][1]) && (
				   T_WHITESPACE  === $code[$i][0]
				|| T_COMMENT     === $code[$i][0]
				|| T_DOC_COMMENT === $code[$i][0]
			))
			{
				$token =& $code[$i];
				unset($code[$i++]);

				$lines = substr_count($token[1], "\n");
				$lines && $token[3] = T_MULTILINE;

				if (isset($tRegistry[$token[0]]))
				{
					$token[2] = $deco;

					foreach ($tRegistry[$token[0]] as $t)
					{
						if (0 === $t[2] || (isset($token[3]) && $token[3] === $t[2]))
						{
							if (false === $t[0]->{$t[1]}($token)) continue 2;
						}
					}
				}

				$deco .= $token[1];

				$line += $lines;
			}
		}

		$deco = $tokens;
		$tokens = array();

		return $deco;
	}

	protected function getTokens($code)
	{
		return $this->parent === $this ? token_get_all($code) : $this->parent->getTokens($code);
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
			list($type, $code, $deco) = $tokens[$j] + array(2 => '');

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

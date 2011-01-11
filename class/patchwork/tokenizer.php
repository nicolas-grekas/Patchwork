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
defined('T_DIR' )         || patchwork_tokenizer::defineNewToken('T_DIR');
defined('T_NS_C')         || patchwork_tokenizer::defineNewToken('T_NS_C');
defined('T_NAMESPACE')    || patchwork_tokenizer::defineNewToken('T_NAMESPACE');
defined('T_NS_SEPARATOR') || patchwork_tokenizer::defineNewToken('T_NS_SEPARATOR');

// Match closing braces opened with T_CURLY_OPEN or T_DOLLAR_OPEN_CURLY_BRACES
patchwork_tokenizer::defineNewToken('T_CURLY_CLOSE');


class patchwork_tokenizer
{
	protected

	$line  = 0,
	$token = array(),
	$index = 0,
	$type  = array(),
	$code  = array(),
	$prevType,
	$anteType,

	$tokenRegistry    = array(),
	$callbackRegistry = array(),

	$parent,
	$dependencies = array(),
	$parents = array(),
	$shared = array(
		'line',
		'token',
		'parents',
		'index',
		'type',
		'code',
		'prevType',
		'anteType',
		'tokenRegistry',
		'callbackRegistry',
		'tokenizerError',
		'nextRegistryIndex',
	);


	private

	$tokenizerError    = array(),
	$registryIndex     = 0,
	$nextRegistryIndex = 0;


	protected static

	$sugar = array(
		T_WHITESPACE  => 1,
		T_COMMENT     => 1,
		T_DOC_COMMENT => 1,
	);


	function __construct(self $parent = null)
	{
		$parent || $parent = $this;
		$this->initialize($parent);
	}

	protected function initialize(self $parent)
	{
		$this->parent = $parent;
		$this->parents =& $this->parent->parents;
		$this->dependencies = (array) $this->dependencies;

		foreach ($this->dependencies as $k => $parent)
		{
			unset($this->dependencies[$k]);

			$k = strtolower('\\' !== $parent[0] ? __CLASS__ . '_' . $parent : substr($parent, 1));

			if (!isset($this->parents[$k]))
			{
				return trigger_error(get_class($this) . ' tokenizer depends on a not initialized one: ' . $parent);
			}

			$this->dependencies[$parent] = $this->parents[$k];
		}

		$parent = strtolower(get_class($this));

		while (!isset($this->parents[$parent]) && false !== $parent)
		{
			$this->parents[$parent] = $this;
			$parent = strtolower(get_parent_class($parent));
		}

		if ($this !== $this->parent)
		{
			foreach (array_keys($this->parent->shared) as $parent)
				$this->$parent =& $this->parent->$parent;

			$this->parent->shared += array_flip((array) $this->shared);
			$this->shared =& $this->parent->shared;
		}
		else
		{
			$this->shared = array_flip((array) $this->shared);
		}

		$this->registryIndex = $this->nextRegistryIndex;
		$this->nextRegistryIndex += 100000;

		empty($this->callbacks) || $this->register();
	}

	function getErrors()
	{
		return $this->tokenizerError;
	}

	function parse($code)
	{
		if ($this->parent !== $this) return $this->parent->parse($code);

		if ('' === $code) return $code;

		$tRegistry =& $this->tokenRegistry;
		$cRegistry =& $this->callbackRegistry;

		$this->token = $this->getTokens($code);

		$line     =& $this->line;     $line     = 1;
		$token    =& $this->token;
		$i        =& $this->index;    $i        = 0;
		$type     =& $this->type;     $type     = array();
		$code     =& $this->code;     $code     = array('');
		$prevType =& $this->prevType; $prevType = false;
		$anteType =& $this->anteType; $anteType = false;

		$j        = 0;
		$curly    = 0;
		$strCurly = array();

		while (isset($token[$i]))
		{
			$t =& $token[$i];
			unset($token[$i++]);

			$lines = 0;
			$typed = 1;

			if (isset($t[1]))
			{
				switch ($t[0])
				{
				case T_WHITESPACE:
				case T_COMMENT:
				case T_DOC_COMMENT:
					$typed = 0;
					// No break;

				case T_CONSTANT_ENCAPSED_STRING:
				case T_ENCAPSED_AND_WHITESPACE:
				case T_OPEN_TAG_WITH_ECHO:
				case T_INLINE_HTML:
				case T_CLOSE_TAG:
				case T_OPEN_TAG:
					$lines = substr_count($t[1], "\n");
					break;

				case T_DOLLAR_OPEN_CURLY_BRACES:
				case T_CURLY_OPEN:
					$strCurly[] = $curly;
					$curly = 0;
					break;
				}
			}
			else
			{
				$t = array($t, $t);

				switch ($t[0])
				{
				case '{': ++$curly; break;
				case '}':
					if (0 > --$curly)
					{
						$t[0]  = T_CURLY_CLOSE;
						$curly = array_pop($strCurly);
					}
					break;
				}
			}

			if (isset($tRegistry[$t[0]]) || ($cRegistry && $typed))
			{
				$t[2] = array($k = $t[0]);

				if (empty($tRegistry[$k])) $callbacks = $cRegistry;
				else if ($cRegistry && $typed)
				{
					$callbacks = $cRegistry + $tRegistry[$k];
					ksort($callbacks);
				}
				else $callbacks = $tRegistry[$k];

				do
				{
					foreach ($callbacks as $k => $c)
					{
						unset($callbacks[$k]);

						if (false === $k = $c[0]->{$c[1]}($t)) continue 3;
						else if (null !== $k && isset($tRegistry[$t[2][] = $k]))
						{
							$callbacks += $tRegistry[$k];
							ksort($callbacks);
							continue 2;
						}
					}

					break;
				}
				while (1);
			}

			$code[++$j] =& $t[1];
			$line += $lines;

			if ($typed)
			{
				$anteType = $prevType;
				$type[$j] = $prevType = $t[0];
			}
		}

		// Free memory thanks to copy-on-write
		$j    = $code;
		$type = $code = array();
		$line = 0;

		return $j;
	}

	protected function setError($message, $type = E_USER_ERROR)
	{
		$this->tokenizerError[] = array($message, (int) $this->line, get_class($this), $type);
	}

	protected function register($method = null)
	{
		null === $method && $method = $this->callbacks;

		$sort = array();

		foreach ((array) $method as $method => $type)
		{
			if (is_int($method))
			{
				isset($sort['']) || $sort[''] =& $this->callbackRegistry;
				$this->callbackRegistry[++$this->registryIndex] = array($this, $type);
			}
			else foreach ((array) $type as $type)
			{
				isset($sort[$type]) || $sort[$type] =& $this->tokenRegistry[$type];
				$this->tokenRegistry[$type][++$this->registryIndex] = array($this, $method);
			}
		}

		foreach ($sort as &$sort) ksort($sort);
	}

	protected function unregister($method = null)
	{
		null === $method && $method = $this->callbacks;

		foreach ((array) $method as $method => $type)
		{
			if (is_int($method))
			{
				foreach ($this->callbackRegistry as $k => $v)
					if (array($this, $type) === $v)
						unset($this->callbackRegistry[$k]);
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

	protected function &getNextToken($offset = 0)
	{
		$i = $this->index;

		do while (isset($this->token[$i], self::$sugar[$this->token[$i][0]])) ++$i;
		while ($offset-- > 0 && ++$i);

		isset($this->token[$i]) || $this->token[$i] = array(T_WHITESPACE, '');

		return $this->token[$i];
	}

	protected function tokenUnshift()
	{
		foreach (func_get_args() as $token)
			$this->token[--$this->index] = $token;

		return false;
	}

	protected function getTokens($code)
	{
		return $this->parent === $this ? token_get_all($code) : $this->parent->getTokens($code);
	}

	static function defineNewToken($name)
	{
		static $offset = 0;
		define($name, --$offset);
	}

	static function export($a)
	{
		if (is_array($a))
		{
			if ($a)
			{
				$i = 0;
				$b = array();

				foreach ($a as $k => $a)
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
			foreach ($v as $k => $v)
			{
				if ("\0" === substr($k, 0, 1)) $k = substr($k, 3);
				$b[$k] = $v;
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
		else if (true  === $a) $b = 'true';
		else if (false === $a) $b = 'false';
		else if (null  === $a) $b = 'null';
		else if (INF   === $a) $b = 'INF';
		else if (NAN   === $a) $b = 'NAN';
		else $b = (string) $a;

		return $b;
	}
}

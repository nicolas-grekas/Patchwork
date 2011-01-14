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


// Match T_STRING variants
patchwork_tokenizer::defineNewToken('T_NAME_NS');       // namespace FOO\BAR;
patchwork_tokenizer::defineNewToken('T_NAME_CLASS');    // class FOOBAR {}; interface FOOBAR {}
patchwork_tokenizer::defineNewToken('T_NAME_FUNCTION'); // function FOOBAR()
patchwork_tokenizer::defineNewToken('T_NAME_CONST');    // class foo {const BAR}
patchwork_tokenizer::defineNewToken('T_USE_NS');        // FOO\bar
patchwork_tokenizer::defineNewToken('T_USE_CLASS');     // new FOO; BAR::...
patchwork_tokenizer::defineNewToken('T_USE_METHOD');    // foo::BAR(); $foo->BAR()
patchwork_tokenizer::defineNewToken('T_USE_PROPERTY');  // $foo->BAR
patchwork_tokenizer::defineNewToken('T_USE_FUNCTION');  // FOOBAR()
patchwork_tokenizer::defineNewToken('T_USE_CONST');     // foo::BAR
patchwork_tokenizer::defineNewToken('T_USE_CONSTANT');  // $foo = BAR
patchwork_tokenizer::defineNewToken('T_GOTO_LABEL');    // goto FOO; or FOO: {...}
patchwork_tokenizer::defineNewToken('T_TYPE_HINT');     // instanceof FOO; function f(BAR $a)
patchwork_tokenizer::defineNewToken('T_PARENT');        // parent
patchwork_tokenizer::defineNewToken('T_SELF');          // self
patchwork_tokenizer::defineNewToken('T_TRUE');          // true
patchwork_tokenizer::defineNewToken('T_FALSE');         // false
patchwork_tokenizer::defineNewToken('T_NULL');          // null
patchwork_tokenizer::defineNewToken('T_IN_STRING');     // "$foo[BAR]"

// New tokens since PHP 5.3
defined('T_GOTO')         || patchwork_tokenizer::defineNewToken('T_GOTO');
defined('T_DIR' )         || patchwork_tokenizer::defineNewToken('T_DIR');
defined('T_NS_C')         || patchwork_tokenizer::defineNewToken('T_NS_C');
defined('T_NAMESPACE')    || patchwork_tokenizer::defineNewToken('T_NAMESPACE');
defined('T_NS_SEPARATOR') || patchwork_tokenizer::defineNewToken('T_NS_SEPARATOR');


class patchwork_tokenizer_stringInfo extends patchwork_tokenizer
{
	protected

	$inConst   = false,
	$inExtends = false,
	$inParam   = 0,
	$inNs      = false,
	$inUse     = false,
	$nsPrefix  = '',
	$nsPreType = 0,
	$callbacks = array(
		'tagString'   => T_STRING,
		'tagConst'    => T_CONST,
		'tagExtends'  => array(T_EXTENDS, T_IMPLEMENTS),
		'tagFunction' => T_FUNCTION,
		'tagNs'       => T_NAMESPACE,
		'tagUse'      => T_USE,
		'tagNsSep'    => T_NS_SEPARATOR,
		'tagNsSep52'  => '\\',
	);


	function removeNsPrefix()
	{
		if (empty($this->nsPrefix)) return;

		$t =& $this->types;
		end($t);

		$p = array(T_STRING, T_NS_SEPARATOR);
		$j = 0;

		while (null !== $i = key($t))
		{
			if ($p[++$j%2] === $t[$i])
			{
				$this->texts[$i] = '';
				unset($t[$i]);
			}
			else break;

			prev($t);
		}

		$this->nsPrefix = '';
		$this->prevType = $this->nsPreType;
	}

	protected function tagString(&$token)
	{
		if ($this->inString & 1) return T_IN_STRING;
		if (T_NS_SEPARATOR !== $p = $this->prevType) $this->nsPrefix = '';

		switch ($token[1])
		{
		case 'self':          if (!$this->nsPrefix) return T_SELF;   break;
		case 'parent':        if (!$this->nsPrefix) return T_PARENT; break;
		case 'goto':          return $this->tokensUnshift(array(T_GOTO,      $token[1]));
		case '__DIR__':       return $this->tokensUnshift(array(T_DIR,       $token[1]));
		case '__NAMESPACE__': return $this->tokensUnshift(array(T_NS_C,      $token[1]));
		case 'namespace':     return $this->tokensUnshift(array(T_NAMESPACE, $token[1]));
		}

		switch (strtolower($token[1]))
		{
		case 'true':   return T_TRUE;
		case 'false':  return T_FALSE;
		case 'null':   return T_NULL;
		}

		switch ($p)
		{
		case T_INTERFACE:
		case T_CLASS: return T_NAME_CLASS;
		case T_GOTO:  return T_GOTO_LABEL;

		case '&': if (T_FUNCTION !== $this->anteType) break;
		case T_FUNCTION: return T_NAME_FUNCTION;

		case ',':
		case T_CONST:
			if ($this->inConst) return T_NAME_CONST;

		default:
			if ($this->inNs ) return T_NAME_NS;
			if ($this->inUse) return T_USE_NS;
		}

		$n = $this->getNextToken();

		if (T_NS_SEPARATOR === $n = $n[0])
		{
			if (T_NS_SEPARATOR === $p)
			{
				$this->nsPrefix .= $token[1];
			}
			else
			{
				$this->nsPrefix  = $token[1];
				$this->nsPreType = $p;
			}

			return T_USE_NS;
		}

		switch (empty($this->nsPrefix) ? $p : $this->nsPreType)
		{
		case ',': if (!$this->inExtends) break;
		case T_NEW:
		case T_EXTENDS:
		case T_IMPLEMENTS: return T_USE_CLASS;
		case T_INSTANCEOF: return T_TYPE_HINT;
		}

		switch ($n)
		{
		case T_DOUBLE_COLON: return T_USE_CLASS;
		case T_VARIABLE:     return T_TYPE_HINT;

		case '(':
			switch ($p)
			{
			case T_OBJECT_OPERATOR:
			case T_DOUBLE_COLON: return T_USE_METHOD;
			default:             return T_USE_FUNCTION;
			}

		case ':':
			if ('{' === $p || ';' === $p) return T_GOTO_LABEL;
			// No break;

		default:
			switch ($p)
			{
			case T_OBJECT_OPERATOR: return T_USE_PROPERTY;
			case T_DOUBLE_COLON:    return T_USE_CONST;

			case '(':
			case ',':
				if (1 === $this->inParam && '&' === $n) return T_TYPE_HINT;
				// No break;
			}
		}

		return T_USE_CONSTANT;
	}

	protected function tagConst(&$token)
	{
		$this->inConst = true;
		$this->register(array('tagConstEnd' => ';'));
	}

	protected function tagConstEnd(&$token)
	{
		$this->inConst = false;
		$this->unregister(array(__FUNCTION__ => ';'));
	}

	protected function tagExtends(&$token)
	{
		$this->inExtends = true;
		$this->register(array('tagExtendsEnd' => '{'));
	}

	protected function tagExtendsEnd(&$token)
	{
		$this->inExtends = false;
		$this->unregister(array(__FUNCTION__ => '{'));
	}

	protected function tagFunction(&$token)
	{
		$this->register(array(
			'tagParamOpenBracket'  => '(',
			'tagParamCloseBracket' => ')',
		));
	}

	protected function tagParamOpenBracket(&$token)
	{
		++$this->inParam;
	}

	protected function tagParamCloseBracket(&$token)
	{
		if (0 >= --$this->inParam)
		{
			$this->inParam = 0;
			$this->unregister(array(
				'tagParamOpenBracket'  => '(',
				'tagParamCloseBracket' => ')',
			));
		}
	}

	protected function tagNs(&$token)
	{
		$t = $this->getNextToken();

		switch ($t[0])
		{
		case T_STRING:
			$this->inNs = true;
			$this->register(array('tagNsEnd' => array('{', ';')));
			// No break;

		case '{':
			return T_NAME_NS;

		case T_NS_SEPARATOR:
			return $this->tagString($token);
		}
	}

	protected function tagNsEnd(&$token)
	{
		$this->inNs = false;
		$this->unregister(array(__FUNCTION__ => array('{', ';')));
	}

	protected function tagUse(&$token)
	{
		if (')' !== $this->prevType)
		{
			$this->inUse = true;
			$this->register(array('tagUseEnd' => ';'));
		}
	}

	protected function tagUseEnd(&$token)
	{
		$this->inUse = false;
		$this->unregister(array(__FUNCTION__ => ';'));
	}

	protected function tagNsSep(&$token)
	{
		if (T_STRING === $this->prevType)
		{
			$this->nsPrefix .= '\\';
		}
		else
		{
			$this->nsPrefix  = '\\';
			$this->nsPreType = $this->prevType;
		}
	}

	protected function tagNsSep52(&$token)
	{
		return $this->tokensUnshift(array(T_NS_SEPARATOR, '\\'));
	}
}

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


// Sub-tokens to tag T_STRING variants
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
		'tagConst'    => T_CONST,
		'tagExtends'  => array(T_EXTENDS, T_IMPLEMENTS),
		'tagFunction' => T_FUNCTION,
		'tagNs'       => T_NAMESPACE,
		'tagUse'      => T_USE,
		'tagNsSep'    => T_NS_SEPARATOR,
		'tagNsSep52'  => '\\',
	),
	$shared = 'nsPrefix';


	function __construct(patchwork_tokenizer $parent)
	{
		// fix for pre-PHP5.3 tokens
		$this->callbacks[T_NAMESPACE > 0 ? 'tagString' : 'tagString52'] = T_STRING;

		parent::__construct($parent);
	}

	function tagString(&$token)
	{
		switch ($this->prevType)
		{
		case T_INTERFACE:
		case T_CLASS: $stype = T_NAME_CLASS; break;
		case T_GOTO:  $stype = T_GOTO_LABEL; break;

		case '&': if (T_FUNCTION !== $this->anteType) break;
		case T_FUNCTION: $stype = T_NAME_FUNCTION; break;

		case ',':
		case T_CONST:
			if ($this->inConst) $stype = T_NAME_CONST;

		default:
			if ($this->inNs ) $stype = T_NAME_NS;
			if ($this->inUse) $stype = T_USE_NS;
		}

		if (empty($token[1][6])) switch (strtolower($token[1]))
		{
		case 'true':   $stype = T_TRUE;   break;
		case 'false':  $stype = T_FALSE;  break;
		case 'null':   $stype = T_NULL;   break;
		case 'self':   'self'   === $token[1] && $stype = T_SELF;   break;
		case 'parent': 'parent' === $token[1] && $stype = T_PARENT; break;
		}

		if (empty($stype))
		{
			if (T_NS_SEPARATOR === $t = $this->prevType)
			{
				if (!$this->nsPreType)
				{
					$this->nsPrefix = '\\';
					$this->nsPreType = $this->anteType;
				}

				$t = $this->nsPreType;
			}

			switch ($t)
			{
			case ',': if (!$this->inExtends) break;
			case T_NEW:
			case T_EXTENDS:
			case T_IMPLEMENTS: $stype = T_USE_CLASS; break;
			case T_INSTANCEOF: $stype = T_TYPE_HINT; break;
			}

			$t = $this->getNextToken();

			if (T_NS_SEPARATOR === $t[0])
			{
				$this->nsPreType || $this->nsPreType = $this->prevType;
				$this->nsPrefix .= $token[1];
				$stype = T_USE_NS;
			}
			else if (empty($stype))
			{
				switch ($t[0])
				{
				case T_DOUBLE_COLON: $stype = T_USE_CLASS; break;
				case T_VARIABLE:     $stype = T_TYPE_HINT; break;

				case '(':
					switch ($this->prevType)
					{
					case T_OBJECT_OPERATOR:
					case T_DOUBLE_COLON: $stype = T_USE_METHOD;   break 2;
					default:             $stype = T_USE_FUNCTION; break 2;
					}

				case ':':
					if ('{' === $this->prevType || ';' === $this->prevType)
					{
						$stype = T_GOTO_LABEL; break 2;
					}
					// No break;
				default:
					switch ($this->prevType)
					{
					case T_OBJECT_OPERATOR: $stype = T_USE_PROPERTY; break 2;
					case T_DOUBLE_COLON:    $stype = T_USE_CONST;    break 2;

					case '(':
					case ',':
						if (1 === $this->inParam && '&' === $t[0])
						{
							$stype = T_TYPE_HINT; break 2;
						}

						// No break;

					default: $stype = T_USE_CONSTANT; break 2;
					}
				}
			}
		}

		$token[2] = $stype;

		if (isset($this->tokenRegistry[$stype]))
		{
			foreach ($this->tokenRegistry[$stype] as $c)
			{
				if (0 === $c[2] || 0 === strcasecmp($token[1], $c[2]))
				{
					if (false === $c[0]->{$c[1]}($token)) return false;
				}
			}
		}

		if ($this->nsPrefix && T_USE_NS !== $stype)
		{
			$this->nsPrefix  = '';
			$this->nsPreType = 0;
		}
	}

	function tagConst(&$token)
	{
		$this->inConst = true;
		$this->register(array('tagConstEnd' => ';'));
	}

	function tagConstEnd(&$token)
	{
		$this->inConst = false;
		$this->unregister(array(__FUNCTION__ => ';'));
	}

	function tagExtends(&$token)
	{
		$this->inExtends = true;
		$this->register(array('tagExtendsEnd' => '{'));
	}

	function tagExtendsEnd(&$token)
	{
		$this->inExtends = false;
		$this->unregister(array(__FUNCTION__ => '{'));
	}

	function tagFunction(&$token)
	{
		$this->register(array(
			'tagParamOpenBracket'  => '(',
			'tagParamCloseBracket' => ')',
		));
	}

	function tagParamOpenBracket(&$token)
	{
		++$this->inParam;
	}

	function tagParamCloseBracket(&$token)
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

	function tagNs(&$token)
	{
		$t = $this->getNextToken();

		switch ($t[0])
		{
		case T_STRING:
			$this->inNs = true;
			$this->register(array('tagNsEnd' => array('{', ';')));
			// No break;

		case '{':
			$token[2] = T_NAME_NS;
			break;

		case T_NS_SEPARATOR:
			$this->tagString($token);
		}
	}

	function tagNsEnd(&$token)
	{
		$this->inNs = false;
		$this->unregister(array(__FUNCTION__ => array('{', ';')));
	}

	function tagUse(&$token)
	{
		if (')' !== $this->prevType)
		{
			$this->inUse = true;
			$this->register(array('tagUseEnd' => ';'));
		}
	}

	function tagUseEnd(&$token)
	{
		$this->inUse = false;
		$this->unregister(array(__FUNCTION__ => ';'));
	}

	function tagNsSep(&$token)
	{
		isset($this->nsPrefix[0]) && $this->nsPrefix .= '\\';
	}

	function removeNsPrefix()
	{
		$t =& $this->type;
		end($t);

		while (null !== $i = key($t))
		{
			if (T_NS_SEPARATOR === $t[$i] || T_STRING === $t[$i])
			{
				$this->code[$i] = '';
				unset($t[$i]);
			}
			else break;

			prev($t);
		}

		$this->nsPrefix = '';
	}


	// fix for pre-PHP5.3 tokens

	function tagString52(&$token)
	{
		switch ($token[1])
		{
		case 'goto':          $token[0] = T_GOTO;      break;
		case '__DIR__':       $token[0] = T_DIR;       break;
		case '__NAMESPACE__': $token[0] = T_NS_C;      break;
		case 'namespace':     $token[0] = T_NAMESPACE; break;

		default: return $this->tagString($token);
		}

		return $this->tokenUnshift($token);
	}

	function tagNsSep52(&$token)
	{
		return $this->tokenUnshift(array(T_NS_SEPARATOR, '\\'));
	}
}

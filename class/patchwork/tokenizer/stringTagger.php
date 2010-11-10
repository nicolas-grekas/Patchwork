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


class patchwork_tokenizer_stringTagger extends patchwork_tokenizer
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
	),
	$shared = 'nsPrefix';


	function tagString(&$token)
	{
		switch ($this->prevType)
		{
		case T_INTERFACE:
		case T_CLASS: $token[3] = T_NAME_CLASS; break;

		case '&': if (T_FUNCTION !== $this->anteType) break;
		case T_FUNCTION: $token[3] = T_NAME_FUNCTION; break;

		case ',': if (!$this->inConst) break;
		case T_CONST: $token[3] = T_NAME_CONST; break;

		default:
			if ($this->inNs ) $token[3] = T_NAME_NS;
			if ($this->inUse) $token[3] = T_USE_NS;
		}

		if (!isset($token[3]))
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
			case T_IMPLEMENTS:
			case T_INSTANCEOF: $token[3] = T_USE_CLASS;
			}

			$t = $this->getNextToken();

			if (T_NS_SEPARATOR === $t[0])
			{
				$this->nsPreType || $this->nsPreType = $this->prevType;
				$this->nsPrefix .= $token[1] . '\\';
				$token[3] = T_USE_NS;
			}
			else if (!isset($token[3]))
			{
				switch ($t[0])
				{
				case T_VARIABLE:
				case T_DOUBLE_COLON: $token[3] = T_USE_CLASS; break;

				case '(':
					switch ($this->prevType)
					{
					case T_OBJECT_OPERATOR:
					case T_DOUBLE_COLON: $token[3] = T_USE_METHOD;   break 2;
					default:             $token[3] = T_USE_FUNCTION; break 2;
					}

				default:
					switch ($this->prevType)
					{
					case T_OBJECT_OPERATOR: $token[3] = T_USE_PROPERTY; break 2;
					case T_DOUBLE_COLON:    $token[3] = T_USE_CONST;    break 2;

					case '(':
					case ',':
						if (1 === $this->inParam && '&' === $t[0])
						{
							$token[3] = T_USE_CLASS; break 2;
						}

						// No break;

					default: $token[3] = T_USE_CONSTANT; break 2;
					}
				}
			}
		}

		if (isset($this->tokenRegistry[$token[3]]))
		{
			foreach ($this->tokenRegistry[$token[3]] as $c)
			{
				if (0 === $c[2] || 0 === strcasecmp($token[1], $c[2]))
				{
					if (false === $c[0]->{$c[1]}($token)) return false;
				}
			}
		}

		if ($this->nsPrefix && T_USE_NS !== $token[3])
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

		if (T_STRING === $t[0])
		{
			$this->inNs = true;
			$this->register(array('tagNsEnd' => array('{', ';')));
		}
		else if (T_NS_SEPARATOR === $t[0])
		{
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
		if (';' === $this->prevType)
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
}

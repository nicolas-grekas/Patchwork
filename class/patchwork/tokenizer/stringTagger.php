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
patchwork_tokenizer::defineNewToken('T_NAME_CLASS');    // class FOOBAR {}; interface FOOBAR {}
patchwork_tokenizer::defineNewToken('T_NAME_FUNCTION'); // function FOOBAR()
patchwork_tokenizer::defineNewToken('T_NAME_CONST');    // class foo {const BAR}
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
	$callbacks = array(
		'tagString'   => T_STRING,
		'tagConst'    => T_CONST,
		'tagExtends'  => array(T_EXTENDS, T_IMPLEMENTS),
		'tagFunction' => T_FUNCTION,
	);


	function tagString(&$token)
	{
		do
		{
			switch ($this->prevType)
			{
			case T_INTERFACE:
			case T_CLASS:      $token[3] = T_NAME_CLASS;    break 2;
			case T_FUNCTION:   $token[3] = T_NAME_FUNCTION; break 2;
			case T_CONST:      $token[3] = T_NAME_CONST;    break 2;

			case T_NEW:
			case T_EXTENDS:
			case T_IMPLEMENTS:
			case T_INSTANCEOF: $token[3] = T_USE_CLASS;     break 2;

			case ',':
				if ($this->inConst)
				{
					$token[3] = T_NAME_CONST; break 2;
				}
				else if ($this->inExtends)
				{
					$token[3] = T_USE_CLASS;  break 2;
				}

				break;

			case '&':
				if (T_FUNCTION === $this->anteType)
				{
					$token[3] = T_NAME_FUNCTION; break 2;
				}

				break;
			}

			$t = $this->getNextToken();

			switch ($t[0])
			{
			case T_VARIABLE:
			case T_DOUBLE_COLON: $token[3] = T_USE_CLASS; break 2;

			case '(':
				switch ($this->prevType)
				{
				case T_OBJECT_OPERATOR:
				case T_DOUBLE_COLON: $token[3] = T_USE_METHOD;   break 3;
				default:             $token[3] = T_USE_FUNCTION; break 3;
				}

			default:
				switch ($this->prevType)
				{
				case T_OBJECT_OPERATOR: $token[3] = T_USE_PROPERTY; break 3;
				case T_DOUBLE_COLON:    $token[3] = T_USE_CONST;    break 3;

				case '(':
				case ',':
					if (1 === $this->inParam && '&' === $t[0])
					{
						$token[3] = T_USE_CLASS; break 3;
					}
					// No break;

				default: $token[3] = T_USE_CONSTANT; break 3;
				}
			}
		}
		while (0);

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
}

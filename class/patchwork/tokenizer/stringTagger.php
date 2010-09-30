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
patchwork_tokenizer::defineNewToken('T_NAME_CLASS');     // class FOOBAR {}; interface FOOBAR {}
patchwork_tokenizer::defineNewToken('T_NAME_FUNCTION');  // function FOOBAR()
patchwork_tokenizer::defineNewToken('T_NAME_CONST');     // class foo {const BAR}
patchwork_tokenizer::defineNewToken('T_USE_CLASS');      // new FOO; BAR::...
patchwork_tokenizer::defineNewToken('T_USE_METHOD');     // foo::BAR(); $foo->BAR()
patchwork_tokenizer::defineNewToken('T_USE_PROPERTY');   // $foo->BAR
patchwork_tokenizer::defineNewToken('T_USE_FUNCTION');   // FOOBAR()
patchwork_tokenizer::defineNewToken('T_USE_CONST');      // foo::BAR
patchwork_tokenizer::defineNewToken('T_USE_CONSTANT');   // $foo = BAR


class patchwork_tokenizer_stringTagger extends patchwork_tokenizer
{
	protected

	$inConst   = false,
	$inExtends = false,
	$callbacks = array(
		'tagString'  => T_STRING,
		'tagConst'   => T_CONST,
		'tagExtends' => array(T_EXTENDS, T_IMPLEMENTS),
	);


	protected function tagString(&$token)
	{
		switch ($this->prevType)
		{
		case T_INTERFACE:
		case T_CLASS:      $token[3] = T_NAME_CLASS;    break;
		case T_FUNCTION:   $token[3] = T_NAME_FUNCTION; break;
		case T_CONST:      $token[3] = T_NAME_CONST;    break;

		case T_NEW:
		case T_EXTENDS:
		case T_IMPLEMENTS:
		case T_INSTANCEOF: $token[3] = T_USE_CLASS;     break;

		case ',':
			if ($this->inConst)
			{
				$token[3] = T_NAME_CONST; break;
			}
			else if ($this->inExtends)
			{
				$token[3] = T_USE_CLASS;  break;
			}
			// No break;

		case '&':
			if (T_FUNCTION === $this->anteType && '&' === $this->prevType)
			{
				$token[3] = T_NAME_FUNCTION; break;
			}
			// No break;

		default:
			$i = $this->position;

			while (isset($this->code[$i][1]) && (
				   T_WHITESPACE  === $this->code[$i][0]
				|| T_COMMENT     === $this->code[$i][0]
				|| T_DOC_COMMENT === $this->code[$i][0]
			)) ++$i;

			if (!isset($this->code[$i])) break;

			switch ($this->code[$i][0])
			{
			case '&':
			case T_VARIABLE:
				if ('(' !== $this->prevType && ',' !== $this->prevType) break;
				// No break

			case T_DOUBLE_COLON: $token[3] = T_USE_CLASS; break;

			case '(':
				switch ($this->prevType)
				{
				case T_OBJECT_OPERATOR:
				case T_DOUBLE_COLON: $token[3] = T_USE_METHOD;   break;
				default:             $token[3] = T_USE_FUNCTION; break;
				}

				break;

			default:
				switch ($this->prevType)
				{
				case T_OBJECT_OPERATOR: $token[3] = T_USE_PROPERTY; break;
				case T_DOUBLE_COLON:    $token[3] = T_USE_CONST;    break;
				default:                $token[3] = T_USE_CONSTANT; break;
				}
			}
		}
	}

	protected function tagConst(&$token)
	{
		$this->inConst = true;
		$this->register(array('tagConstEnd' => ';'));
	}

	protected function tagConstEnd(&$token)
	{
		$this->inConst = false;
		$this->unregister(array('tagConstEnd' => ';'));
	}

	protected function tagExtends(&$token)
	{
		$this->inExtends = true;
		$this->register(array('tagExtendsEnd' => '{'));
	}

	protected function tagExtendsEnd(&$token)
	{
		$this->inExtends = false;
		$this->unregister(array('tagExtendsEnd' => '{'));
	}
}

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


	function tagString(&$token)
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

			if (!isset($this->code[$i])) return;

			switch ($this->code[$i][0])
			{
			case T_VARIABLE:
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

				case '(':
				case ',':
					if ('&' === $this->code[$i][0])
					{
						// Here, we have to decide between
						// "&" as binary operator (T_USE_CONSTANT)
						// and "&" as by ref parameter declaration (T_USE_CLASS)

						$i = count($this->tokens);
						$b = 1;

						while (isset($this->tokens[--$i]))
						{
							switch ($this->tokens[$i][0])
							{
							case ')': ++$b; break;
							case '(': if (0 === --$b) break 2;
							}
						}

						if (isset($this->tokens[--$i][3]) && T_NAME_FUNCTION === $this->tokens[$i][3])
						{
							$token[3] = T_USE_CLASS;
							break;
						}
					}
					// No break;

				default: $token[3] = T_USE_CONSTANT;
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
	}

	function tagConst(&$token)
	{
		$this->inConst = true;
		$this->register(array('tagConstEnd' => ';'));
	}

	function tagConstEnd(&$token)
	{
		$this->inConst = false;
		$this->unregister(array('tagConstEnd' => ';'));
	}

	function tagExtends(&$token)
	{
		$this->inExtends = true;
		$this->register(array('tagExtendsEnd' => '{'));
	}

	function tagExtendsEnd(&$token)
	{
		$this->inExtends = false;
		$this->unregister(array('tagExtendsEnd' => '{'));
	}
}

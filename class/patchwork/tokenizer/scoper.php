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


// Match a new scope opening
patchwork_tokenizer::defineNewToken('T_SCOPE_OPEN');


class patchwork_tokenizer_scoper extends patchwork_tokenizer
{
	protected

	$curly     = 0,
	$scope     = false,
	$scopes    = array(),
	$nextScope = T_OPEN_TAG,
	$callbacks = array(
		'tagFirstScope' => array(T_OPEN_TAG, ';', '{'),
		'tagScopeClose' => array(T_ENDPHP  , '}'),
		'tagNamespace'  => T_NAMESPACE,
		'tagFunction'   => T_FUNCTION,
		'tagClass'      => array(T_CLASS, T_INTERFACE),
	),
	$shared  = 'scope',
	$depends = 'normalizer';


	function tagFirstScope(&$token)
	{
		$t = $this->getNextToken();

		if (T_NAMESPACE === $t[0] || T_DECLARE === $t[0]) return;

		$this->unregister(array(__FUNCTION__ => array(T_OPEN_TAG, ';', '{')));
		$this->  register(array('tagScopeOpen'  => '{'));

		$this->tagScopeOpen($token);
	}

	function tagScopeOpen(&$token)
	{
		if ($this->nextScope)
		{
			$this->scope = (object) array(
				'parent' => $this->scope,
				'type'   => $this->nextScope,
				'token'  => &$token,
			);

			$onClose = array();

			if (isset($this->tokenRegistry[T_SCOPE_OPEN]))
			{
				foreach ($this->tokenRegistry[T_SCOPE_OPEN] as $c)
					if ($c[1] = $c[0]->{$c[1]}($token))
						$onClose[] = $c;
			}

			$this->scopes[] = array($this->curly, $onClose);
			$this->curly = 0;
			$this->nextScope = false;
		}
		else ++$this->curly;
	}

	function tagScopeClose(&$token)
	{
		if (0 > --$this->curly && $this->scopes)
		{
			list($this->curly, $onClose) = array_pop($this->scopes);

			foreach (array_reverse($onClose) as $c)
				$c[0]->{$c[1]}($token);

			$this->scope = $this->scope->parent;
		}
	}

	function tagClass(&$token)
	{
		$this->nextScope = $token[0];
	}

	function tagFunction(&$token)
	{
		$this->nextScope = T_FUNCTION;
		$this->register(array('tagSemiColon'  => ';')); // For abstracts methods
	}

	function tagNamespace(&$token)
	{
		switch ($this->prevType)
		{
		default: return;
		case ';':
		case '}':
		case T_OPEN_TAG:
			$t = $this->getNextToken();
			if (T_STRING === $t[0] || '{' === $t[0])
			{
				$this->nextScope = T_NAMESPACE;

				if ($this->scope)
				{
					$this->tagScopeClose($token);
					$this->  register(array('tagFirstScope' => array(';', '{')));
					$this->unregister(array('tagScopeOpen'  => '{'));
				}
			}
		}
	}

	function tagSemiColon(&$token)
	{
		$this->unregister(array(__FUNCTION__ => ';'));
		$this->nextScope = false;
	}
}

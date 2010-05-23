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


// New token to match scope edges
patchwork_tokenizer::defineNewToken('T_SCOPE_OPEN');
patchwork_tokenizer::defineNewToken('T_SCOPE_CLOSE');

class patchwork_tokenizer_scoper extends patchwork_tokenizer
{
	protected

	$scopes    = array(),
	$nextScope = T_OPEN_TAG,
	$callbacks = array('tagScopeOpen' => T_OPEN_TAG),
	$curly     = 0;


	function __construct(patchwork_tokenizer_normalizer &$parent = null)
	{
		parent::__construct($parent);

		$this->register($this, array(
			'tagScopeOpen'  => array(T_OPEN_TAG, '{'),
			'tagScopeClose' => array(T_ENDPHP  , '}'),
			'tagFunction'   => T_FUNCTION,
			'tagClass'      => T_CLASS,
		));
	}

	function tagScopeOpen($token, $t)
	{
		if ($this->nextScope)
		{
			$t->unregister($this, $this->callbacks);

			$t->code[--$t->position] = array(T_SCOPE_OPEN, '', 0, '', $this->nextScope);

			$this->scopes[] = array($this->curly, &$t->code[$t->position]);
			$this->curly = 0;
			$this->nextScope = false;
		}
		else ++$this->curly;
	}

	function tagScopeClose($token, $t)
	{
		if (0 > --$this->curly && $this->scopes)
		{
			$t->unregister($this, $this->callbacks);
			$scope = array_pop($this->scopes);
			$this->curly = $scope[0];

			$t->code[--$t->position] = array(T_SCOPE_CLOSE, '', 0, '', &$scope[1]);
		}
	}

	function tagCurly()
	{
		++$this->curly;
	}

	function tagClass()
	{
		$this->nextScope = T_CLASS;
	}

	function tagFunction($token, $t)
	{
		$this->nextScope = T_FUNCTION;
		$t->register($this, $this->callbacks = array(
			'tagSemiColon'  => ';', // For abstracts methods
		));
	}

	function tagSemiColon($token, $t)
	{
		$t->unregister($this, $this->callbacks);
		$this->nextScope = false;
	}
}

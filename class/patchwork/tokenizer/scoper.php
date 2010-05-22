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


class patchwork_tokenizer_scoper extends patchwork_tokenizer
{
	protected

	$scopes    = array(),
	$nextScope = T_OPEN_TAG,
	$callbacks = array('tagScopeStart' => T_OPEN_TAG),
	$curly     = 0,
	$scopeStartRegistry = array(),
	$scopeCloseRegistry = array();


	function __construct(patchwork_tokenizer_normalizer &$parent = null)
	{
		parent::__construct($parent);

		$this->register($this, array(
			'tagScopeStart' => array(T_OPEN_TAG, '{'),
			'tagScopeClose' => array(T_ENDPHP  , '}'),
			'tagFunction'   => T_FUNCTION,
			'tagClass'      => T_CLASS,
		));
	}

	function scopeStartRegister($object, $method)
	{
		$this->scopeStartRegistry[] = array($object, $method);
	}

	function scopeCloseRegister($object, $method)
	{
		$this->scopeCloseRegistry[] = array($object, $method);
	}

	function tagScopeStart(&$token, $t)
	{
		if ($this->nextScope)
		{
			$t->unregister($this, $this->callbacks);

			foreach ($this->scopeStartRegistry as $t)
			{
				$t[0]->{$t[1]}($this->nextScope, $token);
			}

			$this->scopes[] = array($this->curly, $this->nextScope, &$token);
			$this->curly = 0;
			$this->nextScope = false;
		}
		else ++$this->curly;
	}

	function tagScopeClose(&$token, $t)
	{
		if (0 > --$this->curly && $this->scopes)
		{
			$t->unregister($this, $this->callbacks);
			$scope = array_pop($this->scopes);
			$this->curly = $scope[0];

			foreach ($this->scopeCloseRegistry as $t)
			{
				$t[0]->{$t[1]}($scope[1], $scope[2], $token);
			}
		}
	}

	function tagCurly($token, $t)
	{
		++$this->curly;
	}

	function tagClass($token, $t)
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

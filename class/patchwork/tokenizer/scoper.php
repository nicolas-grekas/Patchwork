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


// New token to match a new scope opening
patchwork_tokenizer::defineNewToken('T_SCOPE_OPEN');

class patchwork_tokenizer_scoper extends patchwork_tokenizer_normalizer
{
	protected

	$curly     = 0,
	$scopes    = array(),
	$nextScope = T_OPEN_TAG,
	$callbacks = array(
		'tagScopeOpen'  => array(T_OPEN_TAG, '{'),
		'tagScopeClose' => array(T_ENDPHP  , '}'),
		'tagFunction'   => T_FUNCTION,
		'tagClass'      => array(T_CLASS, T_INTERFACE),
	);


	protected function tagScopeOpen(&$token)
	{
		if ($this->nextScope)
		{
			if (T_OPEN_TAG === $token[0])
			{
				$this->unregister(array('tagScopeOpen' => T_OPEN_TAG));
				$this->callbacks = array();
			}

			$token['scopeType'] = $this->nextScope;
			$onClose = array();

			if (isset($this->tokenRegistry[T_SCOPE_OPEN]))
			{
				foreach ($this->tokenRegistry[T_SCOPE_OPEN] as $c)
					if ($c[1] = $c[0]->{$c[1]}($token))
						$onClose[] = $c;
			}

			$this->scopes[] = array($this->curly, &$token, $onClose);
			$this->curly = 0;
			$this->nextScope = false;
		}
		else ++$this->curly;
	}

	protected function tagScopeClose(&$token)
	{
		if (0 > --$this->curly && $this->scopes)
		{
			$this->unregister();
			$scope = array_pop($this->scopes);
			$this->curly = $scope[0];

			$token['scopeType']  =& $scope[1]['scopeType'];
			$token['scopeToken'] =& $scope[1];

			foreach ($scope[2] as $c)
				$c[0]->{$c[1]}($token);
		}
	}

	protected function tagCurly(&$token)
	{
		++$this->curly;
	}

	protected function tagClass(&$token)
	{
		$this->nextScope = $token[0];
	}

	protected function tagFunction(&$token)
	{
		$this->nextScope = T_FUNCTION;
		$this->register($this->callbacks = array(
			'tagSemiColon'  => ';', // For abstracts methods
		));
	}

	protected function tagSemiColon(&$token)
	{
		$this->unregister();
		$this->nextScope = false;
	}
}

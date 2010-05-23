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


class patchwork_tokenizer_globalizer
{
	static function register(patchwork_tokenizer_scoper $tokenizer, $autoglobals)
	{
		$self = new self($autoglobals);
		$tokenizer->register($self, $self->callbacks);
		$tokenizer->scopeStartRegister($self, 'onScopeStart');
		$tokenizer->scopeCloseRegister($self, 'onScopeClose');
	}


	protected

	$callbacks = array(),
	$scope     = array(),
	$scopes    = array();


	function __construct($autoglobals)
	{
		$callbacks = array();

		foreach ((array) $autoglobals as $autoglobals)
		{
			if (!isset(${substr($autoglobals, 1)}) || '$callbacks' === $autoglobals || '$autoglobals' === $autoglobals)
			{
				$callbacks[$autoglobals] = T_VARIABLE;
			}
		}

		$this->callbacks['tagAutoglobals'] = $callbacks;
	}

	function onScopeStart($type, &$token)
	{
		$this->scopes[] = $this->scope;
		$this->scope    = array(&$token, array());
	}

	function tagAutoglobals($token, $t)
	{
		if (   T_DOUBLE_COLON !== $t->tokens[count($t->tokens)-1][0]
			&& in_array($token[1], array_keys($this->callbacks['tagAutoglobals'])) )
		{
			$this->scope[1][] = $token[1];
		}
	}

	function onScopeClose($type)
	{
		if (isset($this->scope[1][0]) && T_FUNCTION === $type)
		{
			$this->scope[0][1] .= 'global ' . implode(',', array_unique($this->scope[1])) . ';';
		}

		$this->scope = array_pop($this->scopes);
	}
}

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
	}


	protected

	$callbacks = array(
		'tagScopeOpen'  => T_SCOPE_OPEN,
		'tagScopeClose' => T_SCOPE_CLOSE,
	),
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

	function tagScopeOpen()
	{
		$this->scopes[] = $this->scope;
		$this->scope    = array();
	}

	function tagAutoglobals($token, $t)
	{
		if ( !isset($this->scope[$token[1]])
			&& T_DOUBLE_COLON !== $t->tokens[count($t->tokens) - 1][0]
			&& in_array($token[1], array_keys($this->callbacks['tagAutoglobals'])) )
		{
			$this->scope[$token[1]] = 1;
		}
	}

	function tagScopeClose(&$token)
	{
		if ($this->scope && T_FUNCTION === $token[4][4])
		{
			$token[4][1] .= 'global ' . implode(',', array_keys($this->scope)) . ';';
		}

		$this->scope = array_pop($this->scopes);
	}
}

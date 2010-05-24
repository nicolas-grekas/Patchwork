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


class patchwork_tokenizer_globalizer extends patchwork_tokenizer_scoper
{
	protected

	$scope     = array(),
	$scopes    = array(),
	$callbacks = array('tagScopeOpen' => T_SCOPE_OPEN);


	function __construct(parent $parent, $autoglobals)
	{
		$callbacks = array();

		foreach ((array) $autoglobals as $autoglobals)
		{
			if ( !isset(${substr($autoglobals, 1)})
				|| '$callbacks'   === $autoglobals
				|| '$autoglobals' === $autoglobals
				|| '$parent'      === $autoglobals )
			{
				$callbacks[$autoglobals] = T_VARIABLE;
			}
		}

		$this->callbacks['tagAutoglobals'] = $callbacks;

		$this->initialize($parent);
	}

	protected function tagScopeOpen(&$token)
	{
		$this->scopes[] = $this->scope;
		$this->scope    = array();

		return 'tagScopeClose';
	}

	protected function tagAutoglobals(&$token)
	{
		if ( !isset($this->scope[$token[1]])
			&& T_DOUBLE_COLON !== $this->prevType
			&& in_array($token[1], array_keys($this->callbacks['tagAutoglobals'])) )
		{
			$this->scope[$token[1]] = 1;
		}
	}

	protected function tagScopeClose(&$token)
	{
		if ($this->scope && T_FUNCTION === $token['scopeType'])
		{
			$token['scopeToken'][1] .= 'global ' . implode(',', array_keys($this->scope)) . ';';
		}

		$this->scope = array_pop($this->scopes);
	}
}

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


class patchwork_tokenizer_globalizer extends patchwork_tokenizer
{
	protected

	$autoglobals = array(),
	$callbacks   = array(
		'tagScopeOpen'   => T_SCOPE_OPEN,
		'tagAutoglobals' => T_VARIABLE,
	),
	$depends = 'patchwork_tokenizer_scoper';


	function __construct(parent $parent, $autoglobals)
	{
		foreach ((array) $autoglobals as $autoglobals)
		{
			if ( !isset(${substr($autoglobals, 1)})
				|| '$autoglobals' === $autoglobals
				|| '$parent'      === $autoglobals )
			{
				$this->autoglobals[$autoglobals] = 1;
			}
		}

		$this->initialize($parent);
	}

	function tagScopeOpen(&$token)
	{
		$this->scope->autoglobals = array();

		return 'tagScopeClose';
	}

	function tagAutoglobals(&$token)
	{
		if (isset($this->autoglobals[$token[1]]) && T_DOUBLE_COLON !== $this->prevType)
		{
			$this->scope->autoglobals[$token[1]] = 1;
		}
	}

	function tagScopeClose(&$token)
	{
		if ($this->scope->autoglobals) switch ($this->scope->type)
		{
		case T_OPEN_TAG: case T_FUNCTION: case T_NAMESPACE:
			$this->scope->token[1] .= 'global ' . implode(',', array_keys($this->scope->autoglobals)) . ';';
		}
	}
}

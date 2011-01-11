<?php /*********************************************************************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class patchwork_tokenizer_constFuncDisabler extends patchwork_tokenizer
{
	protected

	$callbacks = array('tagOpenTag' => T_SCOPE_OPEN),
	$dependencies = array('scoper', 'namespaceInfo');


	protected function tagOpenTag(&$token)
	{
		if (T_NAMESPACE === $this->scope->type && '\\' !== $this->namespace)
		{
			$this->register($this->callbacks = array(
				'tagConstFunc'  => array(T_NAME_FUNCTION, T_NAME_CONST),
				'tagScopeClose' => T_SCOPE_CLOSE,
			));
		}
	}

	protected function tagConstFunc(&$token)
	{
		if (T_CLASS !== $this->scope->type)
		{
			$this->setError("Namespaced functions and constants have been deprecated. Please use static methods and class constants.");
		}
	}

	protected function tagScopeClose(&$token)
	{
		$this->unregister();
	}
}

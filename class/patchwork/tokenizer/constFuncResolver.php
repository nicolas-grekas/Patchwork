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


class patchwork_tokenizer_constFuncResolver extends patchwork_tokenizer
{
	protected

	$openTag,
	$nsLoadSrc = false,
	$callbacks = array('tagOpenTag' => T_SCOPE_OPEN),
	$depends   = array('scoper', 'namespaceInfo');


	function tagOpenTag(&$token)
	{
		if (T_NAMESPACE === $this->scope->type && '\\' !== $this->namespace)
		{
			$this->openTag =& $token;
			$this->register($this->callbacks = array(
				'tagFunction' => T_USE_FUNCTION,
				'tagConstant' => T_USE_CONSTANT,
			));

			return 'tagScopeClose';
		}
	}

	function tagScopeClose(&$token)
	{
		$this->unregister();

		if (false !== $this->nsLoadSrc)
		{
			$this->openTag[1] .= $this->nsLoadSrc . ';';
			$this->nsLoadSrc = false;
		}
	}

	function tagFunction(&$token)
	{
		return T_NS_SEPARATOR !== $this->prevType ? $this->resolveConstFunc($token, 'function_exists') : null;
	}

	function tagConstant(&$token)
	{
		return T_NS_SEPARATOR !== $this->prevType ? $this->resolveConstFunc($token, 'defined') : null;
	}

	protected function resolveConstFunc(&$token, $exists)
	{
		$this->tokenUnshift($token, '\\');

		if (  !$exists($token[1])
			|| $exists($this->namespace . $token[1])
			|| self::nsLoad(substr($this->namespace, 0, -1))
			|| $exists($this->namespace . $token[1])  )
		{
			$this->nsLoadSrc = self::nsLoadSrc(substr($this->namespace, 0, -1));
			$this->tokenUnshift(array(T_NAMESPACE, 'namespace'));
		}

		return false;
	}

	static protected function nsLoad($ns)
	{
		//class_exists($ns, true);
		return false;
	}

	static protected function nsLoadSrc($ns)
	{
		//return "class_exists('{$ns}', true)";
		return false;
	}
}

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


class patchwork_tokenizer_constructor4to5 extends patchwork_tokenizer_classInfo
{
	protected

	$bracket,
	$scopes    = array(false),
	$callbacks = array(
		'tagScopeOpen' => T_SCOPE_OPEN,
		'tagFunction'  => T_FUNCTION,
	);


	protected function tagScopeOpen(&$token)
	{
		$this->scopes[]  = $this->scopes[0];
		$this->scopes[0] = array();

		if (T_CLASS === $token['scopeType'])
		{
			$this->scopes[0]['className'] = $this->class[0]['className'];
			$this->scopes[0]['classKey' ] = strtolower($this->class[0]['className']);
		}

		return 'tagScopeClose';
	}

	protected function tagFunction(&$token)
	{
		if ($this->scopes[0]) $this->register('tagFunctionName');
	}

	protected function tagFunctionName(&$token)
	{
		if ('&' === $token[0]) return;
		$this->unregister(__FUNCTION__);
		if (T_STRING !== $token[0]) return;

		switch (strtolower($token[1]))
		{
		case '__construct': $this->scopes[0] = array(); break;
		case $this->scopes[0]['classKey']:
			$this->scopes[0]['constructorSignature'] = '';
			$this->scopes[0]['constructorArguments'] = array();
			$this->bracket = 0;
			$this->register('catchConstructorSignature');
		}
	}

	protected function catchConstructorSignature(&$token)
	{
		if ('(' === $token[0]) ++$this->bracket;
		else if (')' === $token[0]) --$this->bracket;
		else if (T_VARIABLE === $token[0])
		{
			$this->scopes[0]['constructorArguments'][] = '&' . $token[1];
		}

		$this->scopes[0]['constructorSignature'] .= $token[1];

		$this->bracket <= 0 && $this->unregister(__FUNCTION__);
	}

	protected function tagScopeClose(&$token)
	{
		if (isset($this->scopes[0]['constructorSignature']))
		{
			$s = (object) $this->scopes[0];

			$token[1] = 'function __construct' . $s->constructorSignature
			. '{${""}=array(' . implode(',', $s->constructorArguments) . ');'
			. 'if(' . count($s->constructorArguments) . '<func_num_args())${""}+=func_get_args();'
			. 'call_user_func_array(array($this,"' . $s->className . '"),${""});}'
			. $token[1];
		}

		$this->scopes[0] = $this->scopes[count($this->scopes) - 1];
		array_pop($this->scopes);
	}
}

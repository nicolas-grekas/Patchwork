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

	$bracket   = 0,
	$signature = '',
	$arguments = array(),
	$callbacks = array('tagClassOpen' => T_SCOPE_OPEN);


	function tagClassOpen(&$token)
	{
		if (T_CLASS === $this->scope->type)
		{
			$this->unregister();
			$this->callbacks = array('tagFunction' => T_FUNCTION);
			$this->register();
			return 'tagClassClose';
		}
	}

	function tagFunction(&$token)
	{
		T_CLASS === $this->scope->type && $this->register('tagFunctionName');
	}

	function tagFunctionName(&$token)
	{
		if ('&' === $token[0]) return;
		$this->unregister(__FUNCTION__);
		if (T_STRING !== $token[0]) return;

		switch (strtolower($token[1]))
		{
		case '__construct':
			$this->signature = '';
			$this->unregister();
			break;

		case strtolower($this->class->name):
			$this->register('catchSignature');
			break;
		}
	}

	function catchSignature(&$token)
	{
		if (T_VARIABLE === $token[0])
		{
			$this->arguments[] = '&' . $token[1];
		}
		else if ('(' === $token[0]) ++$this->bracket;
		else if (')' === $token[0]) --$this->bracket;

		$this->signature .= $token[1];

		$this->bracket <= 0 && $this->unregister(__FUNCTION__);
	}

	function tagClassClose(&$token)
	{
		$this->unregister();

		if ('' !== $this->signature)
		{
			$token[1] = 'function __construct' . $this->signature
				. '{${""}=array(' . implode(',', $this->arguments) . ');'
				. 'if(' . count($this->arguments) . '<func_num_args())${""}+=func_get_args();'
				. 'call_user_func_array(array($this,"' . $this->class->name . '"),${""});}'
				. $token[1];

			$this->bracket   = 0;
			$this->signature = '';
			$this->arguments = array();
		}

		$this->callbacks = array('tagClassOpen' => T_SCOPE_OPEN);
		$this->register();
	}
}

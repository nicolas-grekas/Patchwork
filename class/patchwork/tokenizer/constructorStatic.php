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


class patchwork_tokenizer_constructorStatic extends patchwork_tokenizer
{
	protected

	$topClass,

	$construct,
	$destruct,
	$callbacks = array('tagClassOpen' => T_SCOPE_OPEN),
	$depends   = array(
		'patchwork_tokenizer_classInfo',
		'patchwork_tokenizer_scoper',
	);


	function __construct(parent $parent, $topClass)
	{
		$this->initialize($parent);
		$this->topClass = $topClass;
	}

	function tagClassOpen(&$token)
	{
		if (T_CLASS === $this->scope->type)
		{
			$this->unregister();
			$this->register(array('tagFunction' => T_FUNCTION));

			$this->construct = $this->destruct = (int) 0 !== strcasecmp($this->class->name, $this->class->extends);

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
			case '__constructstatic': $this->construct = 2; break;
			case '__destructstatic' : $this->destruct  = 2; break;
		}
	}

	function tagClassClose(&$token)
	{
		$this->unregister('tagFunction');
		$this->register();

		$class = strtolower($this->class->name);

		$this->construct && $token[1] = "const __c_s=" . (2 === $this->construct ? "'{$class}';" : "'';static function __constructStatic(){}") . $token[1];
		$this->destruct  && $token[1] = "const __d_s=" . (2 === $this->destruct  ? "'{$class}';" : "'';static function __destructStatic() {}") . $token[1];

		if ($this->topClass && 0 === strcasecmp($this->topClass, $class))
		{
			$token[1] .= "\$GLOBALS['_patchwork_autoloaded']['{$class}']=1;";

			if ($this->class->extends)
			{
				1 !== $this->construct && $token[1] .= "if('{$class}'==={$class}::__c_s){$class}::__constructStatic();";
				1 !== $this->destruct  && $token[1] .= "if('{$class}'==={$class}::__d_s)\$GLOBALS['_patchwork_destruct'][]='{$class}';";
			}
			else
			{
				2 === $this->construct && $token[1] .= "{$class}::__constructStatic();";
				2 === $this->destruct  && $token[1] .= "\$GLOBALS['_patchwork_destruct'][]='{$class}';";
			}
		}
	}
}

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

	$construct,
	$destruct,
	$callbacks = array('tagClassOpen' => T_SCOPE_OPEN),
	$dependencies = 'classInfo';


	protected function tagClassOpen(&$token)
	{
		if (T_CLASS === $this->scope->type)
		{
			$this->unregister();
			$this->construct = $this->destruct = (int) empty($this->class->extendsSelf);
			$this->register(array(
				'tagFunction'   => T_FUNCTION,
				'tagClassClose' => T_SCOPE_CLOSE,
			));
		}
	}

	protected function tagFunction(&$token)
	{
		if (T_CLASS === $this->scope->type)
		{
			$t = $this->getNextToken();
			if ('&' === $t[0]) $t = $this->getNextToken(1);

			if (T_STRING === $t[0]) switch (strtolower($t[1]))
			{
				case '__constructstatic': $this->construct = 2; break;
				case '__destructstatic' : $this->destruct  = 2; break;
			}
		}
	}

	protected function tagClassClose(&$token)
	{
		$this->unregister(array('tagFunction' => T_FUNCTION));
		$this->register();

		$class = strtolower($this->class->nsName);

		$this->construct && $token[1] = "const __c_s=" . (2 === $this->construct ? "__CLASS__;" : "'';static function __constructStatic(){}") . $token[1];
		$this->destruct  && $token[1] = "const __d_s=" . (2 === $this->destruct  ? "__CLASS__;" : "'';static function __destructStatic() {}") . $token[1];

		if ($this->class->extends)
		{
			1 !== $this->construct && $token[1] .= "if('{$class}'==={$this->class->name}::__c_s){$this->class->name}::__constructStatic();";
			1 !== $this->destruct  && $token[1] .= "if('{$class}'==={$this->class->name}::__d_s)\$GLOBALS['_patchwork_destruct'][]='{$class}';";
		}
		else
		{
			2 === $this->construct && $token[1] .= "{$this->class->name}::__constructStatic();";
			2 === $this->destruct  && $token[1] .= "\$GLOBALS['_patchwork_destruct'][]='{$class}';";
		}
	}
}

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


class patchwork_tokenizer_classname
{
	static function register($tokenizer, $classname)
	{
		$self = new self($classname);
		$tokenizer->register($self, array('tagClass' => T_CLASS));
	}


	protected $classname;

	function __construct($classname)
	{
		$this->classname = $classname;
	}

	function tagClass($token, $t)
	{
		$t->register($this, 'fixClassname');
	}

	function fixClassname(&$token, $t)
	{
		$t->unregister($this, __FUNCTION__);

		if (T_STRING !== $token[0])
		{
			$t->code[--$t->position] = $token;
			$t->code[--$t->position] = array(T_WHITESPACE, ' ');
			$t->code[--$t->position] = array(T_STRING, $this->classname);
			$token = false;
			$t->unregister($this, array('tagClass' => T_CLASS));
		}
	}
}

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


class patchwork_tokenizer_className extends patchwork_tokenizer
{
	protected

	$className,
	$callbacks = array('tagClass' => array(T_CLASS, T_INTERFACE));


	function __construct(parent $parent, $className)
	{
		$this->initialize($parent);

		$this->className = $className;
	}

	function tagClass(&$token)
	{
		$this->register('fixClassName');
	}

	function fixClassName(&$token)
	{
		$this->unregister(__FUNCTION__);

		if (T_STRING !== $token[0])
		{
			$this->code[--$this->position] =& $token;
			$this->code[--$this->position] = array(T_WHITESPACE, ' ');
			$this->code[--$this->position] = array(T_STRING, $this->className);

			$this->unregister();

			return false;
		}
	}
}

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


class patchwork_tokenizer_classInfo extends patchwork_tokenizer_scoper
{
	protected

	$class = false,
	$callbacks = array(
		'tagClass' => array(T_CLASS, T_INTERFACE),
	),
	$shared = 'class';


	function tagClass(&$token)
	{
		$this->class = (object) array(
			'type'       => $token[1],
			'name'       => false,
			'extends'    => false,
			'isFinal'    => T_FINAL    === $this->prevType,
			'isAbstract' => T_ABSTRACT === $this->prevType,
			'scope'      => false,
		);

		$token['class'] = $this->class;

		$this->callbacks = array(
			'tagClassName' => T_STRING,
			'tagExtends'   => T_EXTENDS,
			'tagClassOpen' => T_SCOPE_OPEN,
		);

		$this->register();
	}

	function tagClassName(&$token)
	{
		$this->unregister(array(__FUNCTION__ => T_STRING));
		$this->class->name = $token[1];
	}

	function tagExtends(&$token)
	{
		$this->class->extends = true;
		$this->register('tagExtendsName');
	}

	function tagExtendsName(&$token)
	{
		$this->unregister(__FUNCTION__);
		T_STRING === $token[0] && $this->class->extends = $token[1];
	}

	function tagClassOpen(&$token)
	{
		$this->unregister();

		$this->class->scope = $this->scope;

		return 'tagClassClose';
	}

	function tagClassClose(&$token)
	{
		$this->class = false;
	}
}

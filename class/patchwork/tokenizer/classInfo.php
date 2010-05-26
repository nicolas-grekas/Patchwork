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

	$class = array(array()),
	$callbacks = array(
		'tagClass' => array(T_CLASS, T_INTERFACE),
	),
	$shared = 'class';


	protected function tagClass(&$token)
	{
		$this->class[]  =& $this->class[0];
		$this->class[0] =& $token;

		$token += array(
			'classType'       => $token[1],
			'className'       => false,
			'classExtends'    => false,
			'classIsFinal'    => T_FINAL    === $this->prevType,
			'classIsAbstract' => T_ABSTRACT === $this->prevType,
			'classScope'      => false,
		);

		$this->callbacks = array(
			'tagClassName' => T_STRING,
			'tagExtends'   => T_EXTENDS,
			'tagScopeOpen' => T_SCOPE_OPEN,
		);

		$this->register();
	}

	protected function tagClassName(&$token)
	{
		$this->unregister(array('tagClassName' => T_STRING));
		$this->class[0]['className'] = $token[1];
	}

	protected function tagExtends(&$token)
	{
		$this->class[0]['classExtends'] = true;
	}

	protected function tagScopeOpen(&$token)
	{
		$this->unregister();

		$token['class'] =& $this->class[0];
		$this->class[0]['classScope'] =& $token;

		return 'tagScopeClose';
	}

	protected function tagScopeClose(&$token)
	{
		$token['class'] =& $this->class[0];
		$this->class[0] =& $this->class[count($this->class) - 1];
		array_pop($this->class);
	}
}

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


class patchwork_tokenizer_namespaceResolver extends patchwork_tokenizer
{
	protected

	$callbacks  = array(
		'tagUse'       => T_USE,
		'tagNsResolve' => array(T_USE_CLASS, T_USE_FUNCTION, T_USE_CONSTANT, T_TYPE_HINT),
	),
	$dependencies = array('stringInfo' => 'nsPrefix', 'namespaceInfo' => array('namespace', 'nsResolved'));


	protected function tagUse(&$token)
	{
		if (')' !== $this->prevType)
		{
			$this->register('tagUseEnd');
			$token[1] = ' ';
		}
	}

	protected function tagUseEnd(&$token)
	{
		switch ($token[0])
		{
		case ';':
		case $this->prevType:
			$this->unregister(__FUNCTION__);
			if (';' !== $token[0]) return;
		}

		$token[1] = '';
	}

	protected function tagNsResolve(&$token)
	{
		if ('\\' !== $this->nsResolved[0])
		{
			$this->setError("Unresolved namespaced identifier ({$this->nsResolved}).", E_USER_WARNING);
		}
		else if (isset($this->nsPrefix[0]) ? '\\' !== $this->nsPrefix[0] : $this->namespace)
		{
			$this->dependencies['stringInfo']->removeNsPrefix();

			return $this->tokenUnshift(
				array(T_STRING, substr($this->nsResolved, 1)),
				array(T_NS_SEPARATOR, '\\')
			);
		}
	}
}

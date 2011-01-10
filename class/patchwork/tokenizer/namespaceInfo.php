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


class patchwork_tokenizer_namespaceInfo extends patchwork_tokenizer
{
	protected

	$namespace  = '',
	$nsResolved = '',
	$nsAliases  = array(),
	$nsUse      = array(),
	$callbacks  = array(
		'tagNs'        => T_NAMESPACE,
		'tagUse'       => T_USE,
		'tagNsResolve' => array(T_USE_CLASS, T_USE_FUNCTION, T_USE_CONSTANT, T_TYPE_HINT),
	),
	$shared  = array('namespace', 'nsResolved'),
	$depends = 'stringInfo';


	protected static

	$nsCallbacks  = array(
		'tagNsName' => array(T_STRING, T_NS_SEPARATOR),
		'tagNsEnd'  => array('{', ';'),
	),
	$useCallbacks = array(
		'tagUseAs'   => T_STRING,
		'tagUseNext' => array(',', ';'),
		'tagUseEnd'  => ';',
	);


	function tagNs(&$token)
	{
		if (in_array(T_NAME_NS, $token[2]))
		{
			$this->namespace = '';
			$this->nsAliases = array();
			$this->register(self::$nsCallbacks);
		}
	}

	function tagNsName(&$token)
	{
		$this->namespace .= $token[1];
	}

	function tagNsEnd(&$token)
	{
		'' !== $this->namespace && $this->namespace .= '\\';
		$this->unregister(self::$nsCallbacks);
	}

	function tagUse(&$token)
	{
		if (')' !== $this->prevType)
		{
			$this->register(self::$useCallbacks);
		}
	}

	function tagUseAs(&$token)
	{
		if (T_AS === $this->prevType)
		{
			$this->nsAliases[$token[1]] = '\\' . implode('\\', $this->nsUse);
			$this->nsUse = array();
		}
		else
		{
			$this->nsUse[] = $token[1];
		}
	}

	function tagUseNext(&$token)
	{
		if ($this->nsUse)
		{
			$this->nsAliases[end($this->nsUse)] = '\\' . implode('\\', $this->nsUse);
			$this->nsUse = array();
		}
	}

	function tagUseEnd(&$token)
	{
		$this->unregister(self::$useCallbacks);
	}

	function tagNsResolve(&$token)
	{
		$this->nsResolved = $this->nsPrefix . $token[1];

		if ('' === $this->nsPrefix)
		{
			if (in_array(T_USE_CLASS, $token[2]) || in_array(T_TYPE_HINT, $token[2]))
			{
				$this->nsResolved = empty($this->nsAliases[$token[1]])
					? '\\' . $this->namespace . $token[1]
					: $this->nsAliases[$token[1]];
			}
			else if ('' === $this->namespace)
			{
				$this->nsResolved = '\\' . $this->nsResolved;
			}
		}
		else if ('\\' !== $this->nsPrefix[0])
		{
			$a = explode('\\', $this->nsPrefix . $token[1], 2);

			if ('namespace' === $a[0])
			{
				$a[0] = '' !== $this->namespace ? substr('\\' . $this->namespace, 0, -1) : '';
			}
			else if (isset($this->nsAliases[$a[0]]))
			{
				$a[0] = $this->nsAliases[$a[0]];
			}
			else
			{
				$a[0] = '\\' . $this->namespace . $a[0];
			}

			$this->nsResolved = implode('\\', $a);
		}
	}
}

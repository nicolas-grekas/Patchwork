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


class patchwork_tokenizer_superPositioner extends patchwork_tokenizer_classInfo
{
	protected

	$level,
	$topClass,
	$privateToken,
	$callbacks = array(
		'tagSelf'    => array(T_USE_CLASS => T_STRING),
		'tagClass'   => array(T_CLASS, T_INTERFACE),
		'tagPrivate' => T_PRIVATE,
	);


	function __construct(parent $parent, $level, $topClass)
	{
		$this->initialize($parent);
		$this->level    = $level;
		$this->topClass = $topClass;
	}

	protected function tagSelf(&$token)
	{
		if ('self' === $token[1] && !empty($this->class->name))
		{
			$token[1] = $this->class->name;
		}
	}

	protected function tagClass(&$token)
	{
		$this->register(array(
			'tagClassName' => T_STRING,
			'tagClassOpen' => T_SCOPE_OPEN,
		));

		if ($this->class->isFinal)
		{
			$final = array_pop($this->tokens);

			if (isset($final[2]))
			{
				$token[2] = $final[2] . (isset($token[2]) ? $token[2] : '');
			}
		}
	}

	protected function tagClassName(&$token)
	{
		$this->unregister(array(__FUNCTION__ => T_STRING));
		$token[1] .= '__' . (0 <= $this->level ? $this->level : '00');
		$this->class->realName = strtolower($token[1]);
		0 <= $this->level && $this->register(array('tagSelfName' => T_STRING));
	}

	protected function tagSelfName(&$token)
	{
		if (0 === strcasecmp($this->class->name, $token[1]))
		{
			$token[1] .= '__' . ($this->level ? $this->level - 1 : '00');
		}
	}

	protected function tagClassOpen(&$token)
	{
		$this->unregister(array(
			'tagSelfName' => T_STRING,
			__FUNCTION__  => T_SCOPE_OPEN,
		));

		return 'tagClassClose';
	}

	protected function tagPrivate(&$token)
	{
		// "private static" methods or properties are problematic when considering class superposition.
		// To work around this, we change them to "protected static", and warn about it
		// (except for files in the include path). Side effects exist but should be rare.

		// Look backward and forward for the "static" keyword
		if (T_STATIC === $this->prevType) $this->fixPrivate($token);
		else
		{
			$this->privateToken =& $token;
			$this->register('tagStatic');
		}
	}

	protected function tagStatic(&$token)
	{
		$this->unregister(__FUNCTION__);

		if (T_STATIC === $token[0])
		{
			$this->fixPrivate($this->privateToken);
		}

		unset($this->privateToken);
	}

	protected function fixPrivate(&$token)
	{
		$token[1] = 'protected';
		$token[0] = T_PROTECTED;

		if (0 <= $this->level)
		{
			$this->setError("Private static methods or properties are banned, please use protected static ones instead");
		}
	}

	protected function tagClassClose(&$token)
	{
		$c = $this->class;

		if ($c->isFinal)
		{
			$token[1] .= "final {$c->type} {$c->name} extends {$c->realName} {}";
		}
		else
		{
			if ($this->topClass && 0 === strcasecmp($this->topClass, $c->name))
			{
				$token[1] .= ($c->isAbstract ? 'abstract ' : '') . "{$c->type} {$c->name} extends {$c->realName} {}";
			}

			if ($c->isAbstract)
			{
				$token[1] .= "\$GLOBALS['patchwork_abstract']['{$c->realName}']=1;";
			}
		}
	}
}

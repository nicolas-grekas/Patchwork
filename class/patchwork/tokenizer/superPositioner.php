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
	$isTop,
	$callbacks = array(
		'tagClass'       => array(T_CLASS, T_INTERFACE),
		'tagSelf'        => T_STRING,
		'tagClassConst'  => T_CLASS_C,
		'tagMethodConst' => T_METHOD_C,
		'tagPrivate'     => T_PRIVATE,
	);


	function __construct(parent $parent, $level, $isTop)
	{
		$this->initialize($parent);
		$this->level = $level;
		$this->isTop = $isTop;
	}

	protected function tagClass(&$token)
	{
		$this->register(array(
			'tagClassName' => T_STRING,
			'tagScopeOpen' => T_SCOPE_OPEN,
		));

		if ($token['classIsFinal'])
		{
			$token =& $this->tokens[count($this->tokens) - 1];
			$token[0] = T_WHITESPACE;
			$token[1] = '';
		}
	}

	protected function tagClassName(&$token)
	{
		$this->unregister(array('tagClassName' => T_STRING));
		$token[1] .= '__' . (0 <= $this->level ? $this->level : '00');
		$this->class[0]['classKey'] = strtolower($token[1]);
	}

	protected function tagScopeOpen(&$token)
	{
		$this->unregister(array('tagScopeOpen' => T_SCOPE_OPEN));
		$this->inExtends = false;

		return 'tagScopeClose';
	}

	protected function tagSelf(&$token)
	{
		if ('self' === $token[1] && !empty($this->class[0]['className']))
		{
			$token[1] = $this->class[0]['className'];

			if ( empty($this->class[0]['classScope'])
				&& 0 <= $this->level
				&& !empty($this->class[0]['classExtends']) )
			{
				$token[1] .= '__' . ($this->level ? $this->level - 1 : '00');
			}
		}
	}

	protected function tagClassConst(&$token)
	{
		if (!empty($this->class[0]))
		{
			$this->code[--$this->position] = array(
				T_CONSTANT_ENCAPSED_STRING,
				"'" . $this->class[0]['className'] . "'",
			);

			return false;
		}
	}

	protected function tagMethodConst(&$token)
	{
		if (!empty($this->class[0]))
		{
			// FIXME: This doesn't work when a constant expression is needed
			$this->code[--$this->position] = array(
				T_CONSTANT_ENCAPSED_STRING,
				"('" .  $this->class[0]['className'] . "::'.__FUNCTION__)",
			);

			return false;
		}
	}

	protected function tagPrivate(&$token)
	{
		// "private static" methods or properties are problematic when considering class superposition.
		// To work around this, we change them to "protected static", and warn about it
		// (except for files in the include path). Side effects exist but should be rare.

		// Look backward and forward for the "static" keyword
		if (T_STATIC === $this->prevType) $this->fixPrivate($token);
		else $this->register('tagStatic');
	}

	protected function tagStatic(&$token)
	{
		$this->unregister(__FUNCTION__);

		if (T_STATIC === $token[0])
		{
			$this->fixPrivate($this->tokens[count($this->tokens) - 1]);
		}
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

	protected function tagScopeClose(&$token)
	{
		$class =& $token['class'];

		if ($class['classIsFinal'])
		{
			$token[1] .= "final {$class['classType']} {$class['className']} extends {$class['classKey']} {}";
		}
		else
		{
			if ($this->isTop)
			{
				$token[1] .= ($class['classIsAbstract'] ? 'abstract ' : '')
					. "{$class['classType']} {$class['className']} extends {$class['classKey']} {}"
					. "\$GLOBALS['{$this->isTop}']['" . strtolower($class['className']) . "']=1;";
			}

			if ($class['classIsAbstract'])
			{
				$token[1] .= "\$GLOBALS['patchwork_abstract']['{$class['classKey']}']=1;";
			}
		}
	}
}

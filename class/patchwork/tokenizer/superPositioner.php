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
		'tagSelf'          => array('self' => T_USE_CLASS),
		'tagClass'         => array(T_CLASS, T_INTERFACE),
		'tagPrivate'       => T_PRIVATE,
		'tagRequire'       => array(T_REQUIRE_ONCE, T_INCLUDE_ONCE, T_REQUIRE, T_INCLUDE),
		'tagPatchworkPath' => array('patchworkPath' => T_USE_FUNCTION),
		'tagClassExists'   => array(
			'class_exists'     => T_USE_FUNCTION,
			'interface_exists' => T_USE_FUNCTION,
		)
	);


	function __construct(parent $parent, $level, $topClass)
	{
		if (0 <= $level)
		{
			unset($this->callbacks['tagRequire']);
			unset($this->callbacks['tagClassExists']);
		}

		$this->initialize($parent);
		$this->level    = $level;
		$this->topClass = $topClass;
	}

	function tagSelf(&$token)
	{
		if ('self' === $token[1] && !empty($this->class->name))
		{
			$token[1] = $this->class->name;
		}
	}

	function tagClass(&$token)
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

	function tagClassName(&$token)
	{
		$this->unregister(array(__FUNCTION__ => T_STRING));
		$token[1] .= '__' . (0 <= $this->level ? $this->level : '00');
		$this->class->realName = strtolower($token[1]);
		0 <= $this->level && $this->register(array('tagSelfName' => T_STRING));
	}

	function tagSelfName(&$token)
	{
		if (0 === strcasecmp($this->class->name, $token[1]))
		{
			$token[1] .= '__' . ($this->level ? $this->level - 1 : '00');
		}
	}

	function tagClassOpen(&$token)
	{
		$this->unregister(array(
			'tagSelfName' => T_STRING,
			__FUNCTION__  => T_SCOPE_OPEN,
		));

		return 'tagClassClose';
	}

	function tagPrivate(&$token)
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

	function tagStatic(&$token)
	{
		$this->unregister(__FUNCTION__);

		if (T_STATIC === $token[0])
		{
			$this->fixPrivate($this->privateToken);
		}

		unset($this->privateToken);
	}

	function fixPrivate(&$token)
	{
		$token[1] = 'protected';
		$token[0] = T_PROTECTED;

		if (0 <= $this->level)
		{
			$this->setError("Private static methods or properties are banned, please use protected static ones instead");
		}
	}

	function tagClassClose(&$token)
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

	function tagRequire(&$token)
	{
		// TODO: fetch constant code and use it to inline processed paths

		// Every require|include inside files in the include_path
		// is preprocessed thanks to patchworkProcessedPath().

		$token[1] .= ' patchworkProcessedPath(';
		new patchwork_tokenizer_closeExpression($this, ')');
	}

	function tagPatchworkPath(&$token)
	{
		// Append its fourth arg to patchworkPath
		new patchwork_tokenizer_bracket_patchworkPath($this, $this->level);
	}

	function tagClassExists(&$token)
	{
		// For files in the include_path, always set the 2nd arg of class|interface_exists() to true
		new patchwork_tokenizer_bracket_classExists($this);
	}
}

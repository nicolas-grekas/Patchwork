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


class patchwork_tokenizer_superPositioner extends patchwork_tokenizer
{
	protected

	$level,
	$topClass,
	$callbacks = array(
		'tagSelf'          => array('self'   => T_USE_CLASS),
		'tagParent'        => array('parent' => T_USE_CLASS),
		'tagClass'         => array(T_CLASS, T_INTERFACE),
		'tagPrivate'       => T_PRIVATE,
		'tagRequire'       => array(T_REQUIRE_ONCE, T_INCLUDE_ONCE, T_REQUIRE, T_INCLUDE),
		'tagPatchworkPath' => array('patchworkPath' => T_USE_FUNCTION),
		'tagClassExists'   => array(
			'class_exists'     => T_USE_FUNCTION,
			'interface_exists' => T_USE_FUNCTION,
		)
	),
	$depends = array(
		'patchwork_tokenizer_classInfo',
		'patchwork_tokenizer_constantExpression',
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
		if ('self' === $token[1] && !empty($this->class->name) && empty($this->nsPrefix))
		{
			$token[1] = $this->class->name;
			$this->nsResolved = '\\' . $this->class->nsName;
		}
	}

	function tagParent(&$token)
	{
		if ('parent' === $token[1] && !empty($this->class->extends) && empty($this->nsPrefix))
		{
			$token[1] = $this->nsResolved = $this->class->extends;
			'' === $this->namespace && $token[1] = ltrim($token[1], '\\');
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
			$token =& $this->tokens;
			end($token[0]);
			$i = key($token[0]);
			$token[1][$i] = '';
			unset($token[0][$i]);
		}
	}

	function tagClassName(&$token)
	{
		$this->unregister(array(__FUNCTION__ => T_STRING));
		$token[1] .= '__' . (0 <= $this->level ? $this->level : '00');
		$this->class->realName = $token[1];
		0 <= $this->level && $this->register(array('tagExtendsSelf' => T_USE_CLASS));
	}

	function tagExtendsSelf(&$token)
	{
		if (0 === strcasecmp('\\' . $this->class->nsName, $this->nsResolved))
		{
			$token[1] = $this->class->name . '__' . ($this->level ? $this->level - 1 : '00');
			$this->class->extends = $this->nsResolved = '\\' . $this->namespace . $token[1];
			$this->class->extendsSelf = true;

			empty($this->nsPrefix) || $this->removeNsPrefix();
		}
	}

	function tagClassOpen(&$token)
	{
		$this->unregister(array(
			'tagExtendsSelf' => T_USE_CLASS,
			__FUNCTION__     => T_SCOPE_OPEN,
		));

		return 'tagClassClose';
	}

	function tagPrivate(&$token)
	{
		// "private static" methods or properties are problematic when considering class superposition.
		// To work around this, we change them to "protected static", and warn about it
		// (except for files in the include path). Side effects exist but should be rare.

		// Look backward and forward for the "static" keyword
		if (T_STATIC !== $this->prevType)
		{
			$t = $this->getNextToken();

			if (T_STATIC !== $t[0]) return;
		}

		$token = array(T_PROTECTED, 'protected');

		if (0 <= $this->level)
		{
			$this->setError("Private static methods or properties are banned, please use protected static ones instead");
		}

		return false;
	}

	function tagClassClose(&$token)
	{
		$c = $this->class;

		if ($c->isFinal || ($this->topClass && 0 === strcasecmp($this->topClass, $c->nsName)))
		{
			$a = '';
			$c->isAbstract && $a = 'abstract';
			$c->isFinal    && $a = 'final';

			$token[1] = "}{$a} {$c->type} {$c->name} extends {$c->realName} {" . $token[1]
				. "\$GLOBALS['_patchwork_autoloaded']['" . strtolower($c->nsName) . "']=1;";
		}

		if ($c->isAbstract)
		{
			$a = strtolower($this->namespace . $c->realName);
			$token[1] .= "\$GLOBALS['patchwork_abstract']['{$a}']=1;";
		}
	}

	function tagRequire(&$token)
	{
		// Every require|include inside files in the include_path
		// is preprocessed thanks to patchworkProcessedPath().

		$token['no-autoload-marker'] = true;

		if (!DEBUG && TURBO && $this->nextExpressionIsConstant())
		{
			$a = patchworkProcessedPath($this->expressionValue);
			$token =& $this->code[$this->position][1];

			$token = false === $a
				? "patchworkProcessedPath({$token})"
				: (self::export($a) . str_repeat("\n", substr_count($token, "\n")));
		}
		else
		{
			$this->code[--$this->position] = '(';
			$this->code[--$this->position] = array(T_STRING, 'patchworkProcessedPath');
			$this->code[--$this->position] = array(T_WHITESPACE, ' ');
		}

		new patchwork_tokenizer_closeBracket($this);
	}

	function tagPatchworkPath(&$token)
	{
		if (!isset($this->nsPrefix[0]) || '\\' === $this->nsPrefix[0])
		{
			// Append its fourth arg to patchworkPath()
			new patchwork_tokenizer_bracket_patchworkPath($this, $this->level);
		}
	}

	function tagClassExists(&$token)
	{
		if (!isset($this->nsPrefix[0]) || '\\' === $this->nsPrefix[0])
		{
			// For files in the include_path, always set the 2nd arg of class|interface_exists() to true
			new patchwork_tokenizer_bracket_classExists($this);
		}
	}
}

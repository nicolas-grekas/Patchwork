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


class patchwork_tokenizer_marker extends patchwork_tokenizer
{
	protected

	$inStatic = false,
	$inlineClass = array('self' => 1, 'parent' => 1, 'static' => 1),
	$callbacks = array(
		'tagOpenTag'     => T_OPEN_TAG,
		'tagAutoloader'  => array(T_USE_FUNCTION, T_EVAL, T_REQUIRE_ONCE, T_INCLUDE_ONCE, T_REQUIRE, T_INCLUDE),
		'tagScopeOpen'   => T_SCOPE_OPEN,
		'tagStatic'      => T_STATIC,
		'tagNew'         => T_NEW,
		'tagDoubleColon' => T_DOUBLE_COLON,
	),
	$depends   = array(
		'patchwork_tokenizer_classInfo',
		'patchwork_tokenizer_scoper',
		'patchwork_tokenizer_stringTagger',
		'patchwork_tokenizer_normalizer',
	);


	function __construct(parent $parent = null, $inlineClass)
	{
		foreach ($inlineClass as $inlineClass)
		{
			$this->inlineClass[strtolower($inlineClass)] = 1;
		}

		$this->initialize($parent);
	}

	function tagOpenTag(&$token)
	{
		$this->unregister(array(__FUNCTION__ => T_OPEN_TAG));

		$T = PATCHWORK_PATH_TOKEN;

		$token[1] .= "if(!isset(\$a{$T})){global \$a{$T},\$b{$T},\$c{$T};}isset(\$e{$T})||\$e{$T}=false;";
	}

	function tagAutoloader(&$token)
	{
		switch ($token[0])
		{
		case T_STRING:
			if (!isset(patchwork_tokenizer_aliasing::$autoloader[strtolower($token[1])])) return;
		case T_EVAL: $curly = -1; break;
		default:     $curly =  0; break;
		}

		$T = PATCHWORK_PATH_TOKEN;
		$token[1] = "((\$a{$T}=\$b{$T}=\$e{$T})||1?{$token[1]}";
		new patchwork_tokenizer_closeMarker($this, $curly);
	}

	function tagScopeOpen(&$token)
	{
		$this->inStatic = false;

		if (T_CLASS === $this->scope->type)
		{
			$this->inlineClass[strtolower($this->class->name)] = 1;
			$this->class->extends && $this->inlineClass[strtolower($this->class->extends)] = 1;
			return 'tagClassClose';
		}
	}

	function tagStatic(&$token)
	{
		if (T_FUNCTION === $this->scope->type)
		{
			$this->inStatic = true;
			$this->register(array('tagStaticEnd' => ';'));
		}
	}

	function tagStaticEnd(&$token)
	{
		$this->inStatic = false;
		$this->unregister(array(__FUNCTION__ => ';'));
	}

	function tagNew(&$token)
	{
		$i = $this->position;

		while (isset($this->code[$i][1]) && (
			   T_WHITESPACE  === $this->code[$i][0]
			|| T_COMMENT     === $this->code[$i][0]
			|| T_DOC_COMMENT === $this->code[$i][0]
		)) ++$i;

		if (!isset($this->code[$i])) return;

		if (T_STRING === $this->code[$i][0])
		{
			$c = strtolower($this->code[$i][1]);
			if (isset($this->inlineClass[$c])) return;
		}
		else $c = '';

		$token['marker'] = $c;
	}

	function tagDoubleColon(&$token)
	{
		if (   $this->inStatic
			|| T_CLASS === $this->scope->type
			|| strspn($this->anteType, '(,') // To not break pass by ref, isset, unset and list
		) return;

		if (T_STRING === $this->prevType)
		{
			$c = $this->tokens[count($this->tokens) - 1][1];
			if (isset($this->inlineClass[strtolower($c)])) return;
		}

		$token['marker'] = 1;
	}

	function tagClassClose(&$token)
	{
		$T = PATCHWORK_PATH_TOKEN;
		$c = isset($this->class->realName) ? $this->class->realName : $this->class->name;
		$token[1] .= "\$GLOBALS['c{$T}']['{$c}']=__FILE__.'*" . mt_rand(1, mt_getrandmax()) . "';";
	}
}

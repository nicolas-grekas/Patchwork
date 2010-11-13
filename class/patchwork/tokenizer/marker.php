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
		'patchwork_tokenizer_stringInfo',
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

		0 < $this->scope->markerState || $this->scope->markerState = 1;
	}

	function tagScopeOpen(&$token)
	{
		$this->inStatic = false;
		$this->scope->markerState = 0;

		if (T_FUNCTION === $this->scope->type)
		{
			return 'tagFunctionClose';
		}

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
		$c = $this->getNextToken();

		if (T_STRING === $c[0])
		{
			$c = strtolower($c[1]);
			if (isset($this->inlineClass[$c])) return;
			$c = $this->getMarker($c);
			$this->scope->markerState || $this->scope->markerState = -1;
		}
		else if (T_WHITESPACE === $c[0]) return;
		else
		{
			$T = PATCHWORK_PATH_TOKEN;
			$c = "\$a{$T}=\$b{$T}=\$e{$T}";
			0 < $this->scope->markerState || $this->scope->markerState = 1;
		}

		$c = '&' === $this->prevType ? "patchwork_autoload_marker({$c}," : "(({$c})?";

		$token[1] = $c . $token[1];

		new patchwork_tokenizer_closeMarker($this, 0, '&' === $this->prevType ? ')' : ':0)');
	}

	function tagDoubleColon(&$token)
	{
		if (   $this->inStatic
			|| T_CLASS === $this->scope->type
			|| strspn($this->anteType, '(,') // To not break pass by ref, isset, unset and list
		) return;

		$i = count($this->tokens) - 1;

		if (T_STRING === $this->prevType)
		{
			$c = strtolower($this->tokens[$i][1]);
			if (isset($this->inlineClass[$c])) return;
			$c = $this->getMarker($c);
			$this->scope->markerState || $this->scope->markerState = -1;
		}
		else
		{
			// TODO: handle the else case. Since PHP 5.3, vars are allowed here
			return;
		}

		while (isset($this->tokens[--$i]))
		{
			if (T_DEC !== $this->tokens[$i][0] && T_INC !== $this->tokens[$i][0])
			{
				break;
			}
		}

		$c = '&' === $this->anteType ? "patchwork_autoload_marker({$c}," : "(({$c})?";

		$this->tokens[++$i][1] = $c . $this->tokens[$i][1];

		new patchwork_tokenizer_closeMarker($this, 0, '&' === $this->anteType ? ')' : ':0)');
	}

	function tagFunctionClose(&$token)
	{
		if ($this->scope->markerState)
		{
			$T = PATCHWORK_PATH_TOKEN;
			$this->scope->token[1] .= 0 < $this->scope->markerState
				? "global \$a{$T},\$b{$T},\$c{$T};static \$d{$T}=1;(" . $this->getMarker() . ")&&\$d{$T}&&\$d{$T}=0;"
				: "global \$a{$T},\$c{$T};";
		}
	}

	function tagClassClose(&$token)
	{
		$T = PATCHWORK_PATH_TOKEN;
		$c = strtolower($this->namespace . (isset($this->class->realName) ? $this->class->realName : $this->class->name));
		$token[1] .= "\$GLOBALS['c{$T}']['{$c}']=__FILE__.'*" . mt_rand(1, mt_getrandmax()) . "';";
	}

	protected function getMarker($class = '')
	{
		$T = PATCHWORK_PATH_TOKEN;
		$class = '' !== $class ? "isset(\$c{$T}['{$class}'])||" : "\$e{$T}=\$b{$T}=";
		return $class . "\$a{$T}=__FILE__.'*" . mt_rand(1, mt_getrandmax()) . "'";
	}
}

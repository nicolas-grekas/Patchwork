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


class patchwork_tokenizer_marker extends patchwork_tokenizer_functionAliasing
{
	protected

	$newToken,
	$inStatic = false,
	$inlineClass = array('self' => 1, 'parent' => 1, 'static' => 1),
	$callbacks = array(
		'tagOpenTag'     => T_SCOPE_OPEN,
		'tagAutoloader'  => array(T_USE_FUNCTION, T_EVAL, T_REQUIRE_ONCE, T_INCLUDE_ONCE, T_REQUIRE, T_INCLUDE),
		'tagScopeOpen'   => T_SCOPE_OPEN,
		'tagStatic'      => T_STATIC,
		'tagNew'         => T_NEW,
		'tagDoubleColon' => T_DOUBLE_COLON,
	),
	$dependencies = array('classInfo', 'normalizer');


	function __construct(patchwork_tokenizer $parent = null, $inlineClass)
	{
		foreach ($inlineClass as $inlineClass)
		{
			$this->inlineClass[strtolower($inlineClass)] = 1;
		}

		parent::__construct($parent);
	}

	protected function tagOpenTag(&$token)
	{
		$this->unregister(array(__FUNCTION__ => T_SCOPE_OPEN));
		$T = PATCHWORK_PATH_TOKEN;
		$token[1] .= "if(!isset(\$a{$T})){global \$a{$T},\$b{$T},\$c{$T};}isset(\$e{$T})||\$e{$T}=false;";
	}

	protected function tagAutoloader(&$token)
	{
		if (!empty($token['no-autoload-marker'])) return;

		switch ($token[0])
		{
		case T_STRING:
			if (!isset(self::$autoloader[strtolower(ltrim($this->nsResolved, '\\'))])) return;
		case T_EVAL: $curly = -1; break;
		default:     $curly =  0; break;
		}

		$T = PATCHWORK_PATH_TOKEN;
		$token[1] = "((\$a{$T}=\$b{$T}=\$e{$T})||1?{$token[1]}";
		new patchwork_tokenizer_closeMarker($this, $curly);

		0 < $this->scope->markerState || $this->scope->markerState = 1;
	}

	protected function tagScopeOpen(&$token)
	{
		$this->inStatic = false;
		$this->scope->markerState = 0;

		if (T_FUNCTION === $this->scope->type)
		{
			$this->register(array('tagFunctionClose' => T_SCOPE_CLOSE));
		}
		else if (T_CLASS === $this->scope->type)
		{
			$this->inlineClass[strtolower($this->class->nsName)] = 1;
			$this->class->extends && $this->inlineClass[strtolower($this->class->extends)] = 1;
			$this->register(array('tagClassClose' => T_SCOPE_CLOSE));
		}
	}

	protected function tagStatic(&$token)
	{
		if (T_FUNCTION === $this->scope->type)
		{
			$this->inStatic = true;
			$this->register(array('tagStaticEnd' => ';'));
		}
	}

	protected function tagStaticEnd(&$token)
	{
		$this->inStatic = false;
		$this->unregister(array(__FUNCTION__ => ';'));
	}

	protected function tagNew(&$token)
	{
		$c = $this->getNextToken();

		if (T_WHITESPACE === $c[0]) return;

		$token['prevType'] = $this->prevType;
		$this->newToken =& $token;

		if (T_STRING !== $c[0] && '\\' !== $c[0]) return $this->tagNewClass();

		$this->register(array('tagNewClass' => T_USE_CLASS));
	}

	protected function tagNewClass($token = false)
	{
		if ($token)
		{
			$this->unregister(array(__FUNCTION__ => T_USE_CLASS));

			$c = strtolower(substr($this->nsResolved, 1));
			if (isset($this->inlineClass[$c])) return;
			$c = $this->getMarker($c);
			$this->scope->markerState || $this->scope->markerState = -1;
		}
		else
		{
			$T = PATCHWORK_PATH_TOKEN;
			$c = "\$a{$T}=\$b{$T}=\$e{$T}";
			0 < $this->scope->markerState || $this->scope->markerState = 1;
		}

		$T = $this->newToken['prevType'];
		$c = '&' === $T ? "patchwork_autoload_marker({$c}," : "(({$c})?";

		$this->newToken[1] = $c . $this->newToken[1];

		new patchwork_tokenizer_closeMarker($this, $token ? -1 : 0, '&' === $T ? ')' : ':0)');

		unset($this->newToken['prevType'], $this->newToken);
	}

	protected function tagDoubleColon(&$token)
	{
		if (   $this->inStatic
			|| T_STRING !== $this->prevType
			|| T_CLASS === $this->scope->type
		) return;

		$t =& $this->type;
		end($t);

		$c = strtolower(substr($this->nsResolved, 1));
		if (isset($this->inlineClass[$c])) return;

		do switch (prev($t))
		{
		case '(': case ',': return; // To not break pass by ref, isset, unset and list
		case T_DEC: case T_INC: case T_STRING: case T_NS_SEPARATOR:
			continue 2;
		}
		while (0);

		$c = $this->getMarker($c);
		$c = '&' === pos($t) ? "patchwork_autoload_marker({$c}," : "(({$c})?";
		$this->scope->markerState || $this->scope->markerState = -1;

		new patchwork_tokenizer_closeMarker($this, 0, '&' === pos($t) ? ')' : ':0)');

		next($t);
		$this->code[key($t)] = $c . $this->code[key($t)];
	}

	protected function tagFunctionClose(&$token)
	{
		if ($this->scope->markerState)
		{
			$T = PATCHWORK_PATH_TOKEN;
			$this->scope->token[1] .= 0 < $this->scope->markerState
				? "global \$a{$T},\$b{$T},\$c{$T};static \$d{$T}=1;(" . $this->getMarker() . ")&&\$d{$T}&&\$d{$T}=0;"
				: "global \$a{$T},\$c{$T};";
		}
	}

	protected function tagClassClose(&$token)
	{
		$T = PATCHWORK_PATH_TOKEN;
		$c = strtolower($this->nsName . (isset($this->class->suffix) ? $this->class->suffix : ''));
		$token[1] .= "\$GLOBALS['c{$T}']['{$c}']=__FILE__.'*" . mt_rand(1, mt_getrandmax()) . "';";
	}

	protected function getMarker($class = '')
	{
		$T = PATCHWORK_PATH_TOKEN;
		$class = '' !== $class ? "isset(\$c{$T}['{$class}'])||" : "\$e{$T}=\$b{$T}=";
		return $class . "\$a{$T}=__FILE__.'*" . mt_rand(1, mt_getrandmax()) . "'";
	}
}

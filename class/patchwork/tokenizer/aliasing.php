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


class patchwork_tokenizer_aliasing extends patchwork_tokenizer
{
	protected

	$functionAlias = array(),
	$callbacks = array(
		'tagVariableFunction' => '(',
		'tagFunctionCall'     => array(T_USE_FUNCTION),
	),
	$depends = array(
		'patchwork_tokenizer_classInfo',
		'patchwork_tokenizer_stringTagger',
	);


	function __construct(parent $parent, $alias_map)
	{
		$v = get_defined_functions();

		foreach ($v['user'] as $v)
		{
			if (0 === strncasecmp($v, '__patchwork_', 12))
			{
				$v = strtolower($v);
				$this->functionAlias[substr($v, 12)] = $v;
			}
		}

		if (!$this->functionAlias) return;

		$this->initialize($parent);

		foreach ($alias_map as $k => $v)
		{
			function_exists('__patchwork_' . $k) && $this->functionAlias[$k] = $v;
		}
	}

	function tagVariableFunction(&$token)
	{
		if (   ('}' === $this->prevType || T_VARIABLE === $this->prevType)
			&& !in_array($this->anteType, array(T_NEW, T_OBJECT_OPERATOR, T_DOUBLE_COLON)) )
		{
			$T = PATCHWORK_PATH_TOKEN;
			$t =& $this->tokens;
			$i = count($t) - 1;

			if (T_VARIABLE === $this->prevType && '$' !== $this->anteType)
			{
				if ('this' !== $a = substr($t[$i][1], 1))
				{
					$t[$i][1] = "\${is_string(\${$a})&&function_exists(\$v{$T}='__patchwork_'.\${$a})?'v{$T}':'{$a}'}";
				}
			}
			else
			{
				if ($a = '}' === $this->prevType ? 1 : 0)
				{
					$b = array($i, 0);

					while ($a > 0 && isset($t[--$i]))
					{
						if ('{' === $t[$i][0]) --$a;
						else if ('}' === $t[$i][0]) ++$a;
					}

					$b[1] = $i;
					--$i;

					if ('$' !== $t[$i][0]) return;
				}
				else $b = 0;

				while (isset($t[--$i]) && '$' === $t[$i][0]) ;

				if (in_array($t[$i][0], array(T_NEW, T_OBJECT_OPERATOR, T_DOUBLE_COLON))) return;

				++$i;

				$b && $t[$b[0]][1] = $t[$b[1]][1] = '';

				$t[$i][1] = "\${is_string(\$k{$T}=";
				$t[count($t)-1] .= ")&&function_exists(\$v{$T}='__patchwork_'.\$\$k{$T})?'v{$T}':\$k{$T}}";
			}
		}
	}

	function tagFunctionCall(&$token)
	{
		$a = strtolower($token[1]);

		if (isset($this->functionAlias[$a]))
		{
			$a = $this->functionAlias[$a];
			$a = explode('::', $a, 2);

			if (1 === count($a)) $token[1] = $a[0];
			else if (empty($this->class->name) || strcasecmp($a[0], $this->class->name))
			{
				$this->code[--$this->position] = array(T_STRING, $a[1]);
				$this->code[--$this->position] = array(T_DOUBLE_COLON, '::');
				$this->code[--$this->position] = array(T_STRING, $a[0]);

				return false;
			}
		}
	}
}

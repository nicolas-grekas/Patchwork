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


class patchwork_tokenizer_constantInliner extends patchwork_tokenizer_scoper
{
	protected

	$file,
	$dir,
	$constants,
	$callbacks = array(
		'tagConstant'  => array(T_USE_CONSTANT => T_STRING),
		'tagFileC'     => array(T_FILE, T_DIR),
		'tagLineC'     => T_LINE,
		'tagScopeName' => array(T_CLASS, T_FUNCTION),
		'tagClassC'    => T_CLASS_C,
		'tagMethodC'   => T_METHOD_C,
		'tagFuncC'     => T_FUNC_C,
		'tagSelfC'     => array(T_USE_CLASS => T_STRING),
	),
	$class    = array(''),
	$function = array('');

	protected static $internalConstants = array();


	function __construct(parent $parent, $file, $constants)
	{
		$this->file = self::export($file);
		$this->dir  = self::export(dirname($file));

		$this->constants['__DIR__'] = $this->dir;

		foreach ((array) $constants as $constants)
			if (defined($constants))
				$this->constants[$constants] = self::export(constant($constants));

		if (!self::$internalConstants)
		{
			$constants = get_defined_constants(true);
			unset(
				$constants['user'],
				$constants['standard']['INF'],
				$constants['internal']['TRUE'],
				$constants['internal']['FALSE'],
				$constants['internal']['NULL'],
				$constants['internal']['PHP_EOL']
			);

			foreach ($constants as $constants) self::$internalConstants += $constants;

			foreach (self::$internalConstants as &$constants)
				$constants = self::export($constants);
		}

		$this->constants += self::$internalConstants;

		$this->initialize($parent);

		$this->callbacks = array('tagScopeOpen' => T_SCOPE_OPEN);
	}

	protected function tagConstant(&$token)
	{
		if (isset($this->constants[$token[1]]))
		{
			$token = $this->constants[$token[1]];

			return $this->replaceCode(
				is_int($token) ? T_LNUMBER : (is_float($token) ? T_DNUMBER : T_CONSTANT_ENCAPSED_STRING),
				$token
			);
		}
	}

	protected function tagFileC(&$token)
	{
		return $this->replaceCode(
			T_CONSTANT_ENCAPSED_STRING,
			T_FILE === $token[0] ? $this->file : $this->dir
		);
	}

	protected function tagLineC(&$token)
	{
		return $this->replaceCode(T_LNUMBER, $this->line);
	}

	protected function tagScopeName(&$token)
	{
		$this->nextScope = $token[0];
		$this->register('catchScopeName');
	}

	protected function catchScopeName(&$token)
	{
		$this->unregister(__FUNCTION__);

		if (T_STRING === $token[0])
		{
			switch ($this->nextScope)
			{
			case T_CLASS:    $this->class[]    = $token[1]; break;
			case T_FUNCTION: $this->function[] = $token[1]; break;
			}
		}
		else if (T_FUNCTION === $this->nextScope)
		{
			$this->function[] = '{closure}';
		}
		else return;

		$this->register();
	}

	protected function tagScopeOpen(&$token)
	{
		$this->unregister();

		return 'tagScopeClose';
	}

	protected function tagScopeClose(&$token)
	{
		switch ($this->scope->type)
		{
		case T_CLASS:    array_pop($this->class);    break;
		case T_FUNCTION: array_pop($this->function); break;
		}
	}

	protected function tagClassC(&$token)
	{
		return $this->replaceCode(T_CONSTANT_ENCAPSED_STRING, "'" . end($this->class) . "'");
	}

	protected function tagMethodC(&$token)
	{
		$token = array(end($this->class), end($this->function));
		$token = $token[0] && $token[1] ? "'" . $token[0] . '::' . $token[1] . "'" : "''";

		return $this->replaceCode(T_CONSTANT_ENCAPSED_STRING, $token);
	}

	protected function tagFuncC(&$token)
	{
		return $this->replaceCode(T_CONSTANT_ENCAPSED_STRING, "'" . end($this->function) . "'");
	}

	protected function tagSelfC(&$token)
	{
		if ('self' === $token[1])
		{
			$token[1] = end($this->class);
			$token[1] || $token[1] = 'self';
		}
	}

	protected function replaceCode($type, $code)
	{
		$this->code[--$this->position] = array($type, $code);

		return false;
	}
}

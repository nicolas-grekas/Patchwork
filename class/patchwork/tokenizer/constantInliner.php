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


class patchwork_tokenizer_constantInliner extends patchwork_tokenizer
{
	protected

	$file,
	$dir,
	$constants,
	$nextScope = '',
	$callbacks = array(
		'tagScopeOpen' => T_SCOPE_OPEN,
		'tagConstant'  => T_USE_CONSTANT,
		'tagFileC'     => array(T_FILE, T_DIR),
		'tagLineC'     => T_LINE,
		'tagScopeName' => array(T_CLASS, T_FUNCTION),
		'tagClassC'    => T_CLASS_C,
		'tagMethodC'   => T_METHOD_C,
		'tagFuncC'     => T_FUNC_C,
		'tagNsC'       => T_NS_C,
	),
	$depends = array(
		'patchwork_tokenizer_namespaceInfo',
		'patchwork_tokenizer_scoper',
	);

	protected static $internalConstants = array();


	function __construct(parent $parent, $file, $constants)
	{
		$this->file = self::export($file);
		$this->dir  = self::export(dirname($file));

		foreach ((array) $constants as $constants) if (defined($constants))
		{
			if (false !== $c = strrpos($constants, '\\'))
			{
				$constants = 0 !== $c
					? strtolower(substr($constants, 1, $c)) . substr($constants, $c)
					: substr($constants, 1);
			}

			$this->constants[$constants] = self::export(constant($constants));
		}

		if (!self::$internalConstants)
		{
			$constants = get_defined_constants(true);
			unset($constants['user']);

			foreach ($constants as $constants) self::$internalConstants += $constants;

			unset( // Idempotent constants
				self::$internalConstants['TRUE'],
				self::$internalConstants['FALSE'],
				self::$internalConstants['NULL'],
				self::$internalConstants['INF'],
				self::$internalConstants['NAN']
			);

			foreach (self::$internalConstants as &$constants)
				$constants = self::export($constants);
		}

		$this->constants += self::$internalConstants;

		$this->initialize($parent);
	}

	function tagConstant(&$token)
	{
		if ('\\' === $this->nsResolved[0])
		{
			$c = strrpos($this->nsResolved, '\\');
			$c = 0 !== $c
				? strtolower(substr($this->nsResolved, 1, $c)) . substr($this->nsResolved, $c)
				: substr($this->nsResolved, 1);

			if (isset($this->constants[$c]))
			{
				$token[0] = T_CONSTANT_ENCAPSED_STRING;
				$token[1] = $this->constants[$token[1]];
				$this->code[--$this->position] = $token;

				empty($this->nsPrefix) || $this->removeNsPrefix($token);

				return false;
			}
		}
	}

	function tagFileC(&$token)
	{
		return $this->replaceCode(
			T_CONSTANT_ENCAPSED_STRING,
			T_FILE === $token[0] ? $this->file : $this->dir
		);
	}

	function tagLineC(&$token)
	{
		return $this->replaceCode(T_LNUMBER, $this->line);
	}

	function tagScopeName(&$token)
	{
		$t = $this->getNextToken();

		T_STRING === $t[0] && $this->nextScope = $t[1];
	}

	function tagScopeOpen(&$token)
	{
		if ($this->scope->parent)
		{
			$this->scope->classC = $this->scope->parent->classC;
			$this->scope->funcC  = $this->scope->parent->funcC ;

			switch ($this->scope->type)
			{
			case T_CLASS:
				$this->scope->classC = $this->namespace . $this->nextScope;
				break;

			case T_FUNCTION:
				$this->scope->funcC = '' !== $this->nextScope
					? ($this->scope->classC ? '' : $this->namespace) . $this->nextScope
					: ($this->namespace . '{closure}');
				break;
			}
		}
		else
		{
			$this->scope->classC = $this->scope->funcC  = '';
		}

		$this->nextScope = '';
	}

	function tagClassC(&$token)
	{
		return $this->replaceCode(T_CONSTANT_ENCAPSED_STRING, "'{$this->scope->classC}'");
	}

	function tagMethodC(&$token)
	{
		$c = $this->scope->classC && $this->scope->funcC
			? "'{$this->scope->classC}::{$this->scope->funcC}'"
			: "''";

		return $this->replaceCode(T_CONSTANT_ENCAPSED_STRING, $c);
	}

	function tagFuncC(&$token)
	{
		return $this->replaceCode(T_CONSTANT_ENCAPSED_STRING, "'{$this->scope->funcC}'");
	}

	function tagNsC(&$token)
	{
		return $this->replaceCode(T_CONSTANT_ENCAPSED_STRING, "'" . substr($this->namespace, 0, -1) . "'");
	}

	function replaceCode($type, $code)
	{
		$this->code[--$this->position] = array($type, $code);

		return false;
	}
}

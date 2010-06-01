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


class patchwork_tokenizer_constantInliner extends patchwork_tokenizer_stringTagger
{
	protected

	$constants,
	$callbacks = array(
		'tagConstant' => array(T_USE_CONSTANT => T_STRING)
	);

	protected static $internalConstants = array();


	function __construct(parent $parent, $constants)
	{
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
	}

	protected function tagConstant(&$token)
	{
		if (isset($this->constants[$token[1]]))
		{
			$token = $this->constants[$token[1]];
			$this->code[--$this->position] = array(
				is_int($token) ? T_LNUMBER : (is_float($token) ? T_DNUMBER : T_CONSTANT_ENCAPSED_STRING),
				$token
			);

			return false;
		}
	}
}

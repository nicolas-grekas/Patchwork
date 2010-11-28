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


class patchwork_tokenizer_T extends patchwork_tokenizer
{
	protected

	$callbacks = array('tagT' => array('T' => T_USE_FUNCTION)),
	$depends   = array(
		'patchwork_tokenizer_stringInfo',
		'patchwork_tokenizer_constantExpression',
	);


	function tagT(&$token)
	{
		if (!isset($this->nsPrefix[0]) || '\\' === $this->nsPrefix[0])
		{
			++$this->position;

			if ($this->nextExpressionIsConstant())
			{
				if ($_SERVER['PATCHWORK_LANG'])
				{
					// Add the string to the translation table
					TRANSLATOR::get($this->expressionValue, $_SERVER['PATCHWORK_LANG'], false);
				}
			}
			else
			{
				new patchwork_tokenizer_bracket_T($this);
			}

			--$this->position;
		}
	}
}

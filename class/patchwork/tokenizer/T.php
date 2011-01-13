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

	$callbacks = array('tagT' => T_USE_FUNCTION),
	$dependencies = array('namespaceInfo' => 'nsResolved', 'constantExpression' => 'expressionValue');


	protected function tagT(&$token)
	{
		if ('\T' === strtoupper($this->nsResolved))
		{
			++$this->index;

			if ($this->dependencies['constantExpression']->nextExpressionIsConstant())
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

			--$this->index;
		}
	}
}

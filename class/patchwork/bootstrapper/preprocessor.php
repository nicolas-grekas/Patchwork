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


class patchwork_bootstrapper_preprocessor__0
{
	protected $tokenizer;

	function staticPass1($file)
	{
		if ('' === $code = file_get_contents($file)) return '';

		$t = new patchwork_tokenizer_normalizer;
		$t = $this->tokenizer = new patchwork_tokenizer_staticState($t);

		if( (defined('DEBUG') && DEBUG)
			&& !empty($GLOBALS['CONFIG']['debug.scream'])
				|| (defined('DEBUG_SCREAM') && DEBUG_SCREAM) )
		{
			new patchwork_tokenizer_scream($t);
		}

		$code = $t->getRunonceCode($code);

		if ($t = $t->getErrors())
		{
			$t = $t[0];
			$t = addslashes("{$t[0]} in {$file}") . ($t[1] ? " on line {$t[1]}" : '');

			$code .= "die('Patchwork error: {$t}');";
		}

		return $code;
	}

	function staticPass2()
	{
		if (empty($this->tokenizer)) return '';
		$code = substr($this->tokenizer->getRuntimeCode(), 5);
		$this->tokenizer = null;
		return $code;
	}
}

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


class patchwork_tokenizer_bracketVerifier extends patchwork_tokenizer
{
	protected

	$bracket = array(),
	$callbacks = array(
		'pushBracket' => array('{', '[', '('),
		'popBracket'  => array('}', ']', ')'),
	);


	protected function pushBracket(&$token)
	{
		switch ($token[0])
		{
		case '{': $this->bracket[] = '}'; break;
		case '[': $this->bracket[] = ']'; break;
		case '(': $this->bracket[] = ')'; break;
		}
	}

	protected function popBracket(&$token)
	{
		if ($token[0] !== $last = array_pop($this->bracket))
		{
			$this->unregister();

			$last && $last = ", expecting `{$last}'";

			$this->setError("Syntax error, unexpected `{$token[0]}'{$last}", $token[2]);
		}
	}
}

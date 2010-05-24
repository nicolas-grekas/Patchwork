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
	public $bracketError = false;

	protected

	$bracket = array(),
	$callbacks = array(
		'pushBracket' => array('{', '[', '('),
		'popBracket'  => array('}', ']', ')'),
	),
	$shared = 'bracketError';


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

			$this->bracketError = (object) array(
				'unexpected' => $token[1],
				'expecting' => $last,
				'line' => $token[2]
			);
		}
	}
}

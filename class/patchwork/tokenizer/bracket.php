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


class patchwork_tokenizer_bracket
{
	protected

	$callbacks = array(
		'pushBracket' => array('{', '[', '('),
		'popBracket'  => array('}', ']', ')'),
	);

	static function register($tokenizer, &$error = null)
	{
		$self = new self($error);
		$tokenizer->register($self, $self->callbacks);
	}


	protected

	$error,
	$bracket = array();


	function __construct(&$error)
	{
		$this->error =& $error;
		$error = false;
	}

	function pushBracket($token)
	{
		switch ($token[0])
		{
		case '{': $this->bracket[] = '}'; break;
		case '[': $this->bracket[] = ']'; break;
		case '(': $this->bracket[] = ')'; break;
		}
	}

	function popBracket($token, $t)
	{
		if ($token[0] !== $last = array_pop($this->bracket))
		{
			$t->unregister($this, $this->callbacks);

			$this->error = (object) array(
				'unexpected' => $token[1],
				'expecting' => $last,
				'line' => $token[2]
			);
		}
	}
}

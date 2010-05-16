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

	static function register($tokenizer, $file, &$error)
	{
		$file = new self($file, $error);
		$tokenizer->register($file, $file->callbacks);
	}


	protected

	$file,
	$error,
	$bracket = array();


	function __construct($file, &$error)
	{
		$this->file = $file;
		$this->error =& $error;
		$this->bracket;
		$error = '';
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

	function popBracket($token, $tokenizer)
	{
		if ($token[0] !== $last = array_pop($this->bracket))
		{
			$last = $last ? ", expecting `{$last}'" : '';
			$this->error = "Patchwork error: Syntax error, unexpected `{$token[1]}'{$last} in {$this->file} on line {$token[2]}";
			$tokenizer->unregister($this, $this->callbacks);
		}
	}
}

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


class patchwork_tokenizer_file
{
	static function register($tokenizer, $file)
	{
		$self = new self($file);
		$tokenizer->register($self, array('fixFile' => array(T_FILE, T_DIR)));
	}


	protected $file, $dir;

	function __construct($file)
	{
		$this->file = patchwork_tokenizer::export($file);
		$this->dir  = patchwork_tokenizer::export(dirname($file));
	}

	function fixFile(&$token, $t)
	{
		$t->code[--$t->position] = array(
			T_CONSTANT_ENCAPSED_STRING,
			T_FILE === $token[0] ? $this->file : $this->dir
		);
		$token = false;
	}
}

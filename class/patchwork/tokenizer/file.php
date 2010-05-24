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


class patchwork_tokenizer_file extends patchwork_tokenizer
{
	protected

	$file,
	$dir,
	$callbacks = array('fixFile' => array(T_FILE, T_DIR));


	function __construct(parent $parent, $file)
	{
		$this->initialize($parent);

		$this->file = self::export($file);
		$this->dir  = self::export(dirname($file));
	}

	protected function fixFile(&$token)
	{
		$this->code[--$this->position] = array(
			T_CONSTANT_ENCAPSED_STRING,
			T_FILE === $token[0] ? $this->file : $this->dir
		);

		return false;
	}
}

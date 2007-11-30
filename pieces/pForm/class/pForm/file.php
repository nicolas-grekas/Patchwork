<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class extends pForm_text
{
	protected

	$type = 'file',
	$isfile = true,
	$isdata = false;


	protected function init(&$param)
	{
		$this->valid_args[] = $this->maxlength = isset($param['maxlength']) ? (int) $param['maxlength'] : 0;

		$this->valid = isset($param['valid']) ? $param['valid'] : '';
		if (!$this->valid) $this->valid = 'file';

		$i = 0;
		while(isset($param[$i])) $this->valid_args[] =& $param[$i++];

		$this->status = FILTER::getFile($this->form->filesValues[$this->name], $this->valid, $this->valid_args);
		$this->value = $this->status;
	}

	protected function addJsValidation($a)
	{
		$a->_valid = new loop_array(array('char', isset($this->valid_args[1]) ? $this->valid_args[1] : ''));
		return $a;
	}
}

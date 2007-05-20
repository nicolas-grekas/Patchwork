<?php /*********************************************************************
 *
 *   Copyright : (C) 2006 Nicolas Grekas. All rights reserved.
 *   Email     : nicolas.grekas+patchwork@espci.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL, see COPYING
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


class extends iaForm_hidden
{
	protected $type = 'text';
	protected $maxlength = 255;

	protected function init(&$param)
	{
		parent::init($param);
		if (isset($param['maxlength']) && $param['maxlength'] > 0) $this->maxlength = (int) $param['maxlength'];

		if (false !== strpos($this->value, "\r")) $this->value = strtr(str_replace("\r\n", "\n", $this->value), "\r", "\n");

		if (u::strlen($this->value) > $this->maxlength) $this->value = u::substr($this->value, 0, $this->maxlength);
	}

	protected function get()
	{
		$a = parent::get();
		if ($this->maxlength) $a->maxlength = $this->maxlength;
		return $a;
	}

	protected function addJsValidation($a)
	{
		$a->_valid = new loop_array(array_merge(array($this->valid), $this->valid_args));
		return $a;
	}
}

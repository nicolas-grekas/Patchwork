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
	protected
		$type = 'submit'
		$isdata = false;

	protected function init(&$param)
	{
		if (isset($this->form->rawValues[$this->name])) $this->status = true;
		else if (isset($this->form->rawValues[$this->name.'_x']) && isset($this->form->rawValues[$this->name.'_y']))
		{
			$x =& $this->form->rawValues;

			$this->value = array(
				isset($x[$this->name.'_x']) ? (int) $x[$this->name.'_x'] : 0, 
				isset($x[$this->name.'_y']) ? (int) $x[$this->name.'_y'] : 0, 
			);

			unset($x);

			$x = $this->value[0];
			$y = $this->value[1];

			$this->status = false!=$x && false!=$y;
			$this->value = $this->status ? array($x, $y) : array();
		}
		else $this->status = '';

		$this->form->setEnterControl($this->name);
	}

	protected function get()
	{
		$a = parent::get();
		unset($a->value);
		return $a;
	}
}

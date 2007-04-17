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


class extends iaForm_text
{
	protected $src;
	protected $lock;

	protected function init(&$param)
	{
		parent::init($param);
		if (isset($param['src'])) $this->src = $param['src'];
		if (isset($param['lock']) && $param['lock']) $this->lock = 1;
	}

	protected function get()
	{
		$a = parent::get();

		$this->agent = 'QSelect/input';

		if (isset($this->src)) $a->_src = $this->src;
		if (isset($this->lock)) $a->_lock = $this->lock;

		return $a;
	}
}

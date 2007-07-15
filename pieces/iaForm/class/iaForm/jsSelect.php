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


class extends iaForm_select
{
	protected $src;

	protected function init(&$param)
	{
		unset($param['item']);
		unset($param['sql']);
		isset($param['valid']) || $param['valid'] = 'char';

		parent::init($param);

		isset($param['src']) && $this->src = $param['src'];
	}

	protected function get()
	{
		$a = parent::get();

		$this->agent = 'form/jsSelect';

		if (isset($this->src)) $a->_src_ = $this->src;

		if ($this->status) $a->_value = new loop_array((array) $this->value, false);

		unset($a->_type);

		return $a;
	}
}

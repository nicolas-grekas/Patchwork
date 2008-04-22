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
	protected $src, $lock, $textarea;

	protected function init(&$param)
	{
		parent::init($param);

		isset($param['src'])      && $this->src      = $param['src'];
		isset($param['textarea']) && $this->textarea = $param['textarea'];
		empty($param['lock'])     || $this->lock     = 1;
	}

	protected function get()
	{
		$a = parent::get();

		$this->agent = 'QSelect/input';

		isset($this->src)      && $a->_src      = $this->src;
		isset($this->textarea) && $a->_textarea = $this->textarea;
		isset($this->lock)     && $a->_lock     = $this->lock;

		return $a;
	}
}

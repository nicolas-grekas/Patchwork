<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 3 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


class extends iaForm_text
{
	protected $maxlength = 10;

	protected function init(&$param)
	{
		if (!isset($param['valid'])) $param['valid'] = 'date';
		if (isset($param['default']) && '0000-00-00' == $param['default']) unset($param['default']);

		parent::init($param);
	}

	protected function get()
	{
		$a = parent::get();
		$a->onchange = 'this.value=valid_date(this.value)';
		return $a;
	}

	function getDbValue()
	{
		if ($v = $this->getValue())
		{
			if (preg_match("'^(\d{2})-(\d{2})-(\d{4})$'", $v, $v))
			{
				$v = $v[3] . '-' . $v[2] . '-' . $v[1];
			}
			else $v = '';
		}

		return (string) $v;
	}
}

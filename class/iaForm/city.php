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


class extends iaForm_QSelect
{
	protected $src = 'QSelect/city';

	protected function init(&$param)
	{
		if (isset($param['default']))
		{
			$a = strpos($param['default'], ':');

			if (false !== $a) $param['default'] = substr($param['default'], $a + 1);
		}

		parent::init($param);

		if (!$this->value)
		{
			$a = strpos($this->value, ':');

			if (false !== $a) $this->value = str_replace($this->value, ':', '_');
		}
	}

	function getDbValue()
	{
		if ($this->value)
		{
			$sql = "SELECT c.city_id
				FROM geocities c, geosearch s
				WHERE c.city_id=s.city_id AND s.search='" . LIB::getKeywords($this->value) . "'";
			$city_id = (int) DB()->queryOne($sql);
		
			$this->value = $city_id . ':' . $this->value;
		}

		return $this->value;
	}
}

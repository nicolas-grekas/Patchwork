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


class extends loop
{
	protected

	$array,
	$isAssociative = true;


	function __construct($array, $filter = '', $isAssociative = null)
	{
		$this->array =& $array;
		if ($filter) $this->addFilter($filter);
		$this->isAssociative = $isAssociative!==null ? $isAssociative : $filter!==false;
	}

	protected function prepare() {return count($this->array);}

	protected function next()
	{
		if (list($key, $value) = each($this->array))
		{
			$data = array('VALUE' => &$value);
			if ($this->isAssociative) $data['KEY'] =& $key;

			return (object) $data;
		}
		else reset($this->array);
	}
}

function filter_rawArray($data)
{
	return $data->VALUE;
}

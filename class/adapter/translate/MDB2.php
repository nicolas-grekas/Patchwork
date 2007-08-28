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


class extends TRANSLATE
{
	protected

	$db,
	$table = 'translation';


	function __construct($options)
	{
		$this->db = DB();
		isset($options['table']) && $this->table = $options['table'];
	}

	function search($string, $lang)
	{
		$string = $this->db->quote($string);

		$sql = "SELECT {$lang} FROM {$this->table} WHERE __={$string}";
		if ($row = $this->db->queryRow($sql))
		{
			return $row->$lang;
		}
		else
		{
			$sql = "INSERT INTO {$this->table} (__) VALUES ({$string})";
			$this->db->exec($sql);
			return '';
		}
	}
}

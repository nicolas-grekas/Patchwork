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


class extends agent
{
	public $argv = array('q');

	protected $maxage = -1;

	function control() {}

	function compose($o)
	{
		$sql = $this->argv->q;
		$sql = ('*' == $sql ? '' : LIB::getKeywords($sql));
		$sql = sqlite_escape_string($sql);

		switch ($a = substr($sql, 0, 3))
		{
		case 'st ': $sql = 'saint ' . substr($sql, 3); break;

		case 'agi':
		case 'ayi':
			if ('os ' == substr($sql, 3, 3))
			{
				$sql = substr($sql, 5);
				$sql = "search GLOB 'agios{$sql}*' OR search GLOB 'ayios{$sql}*'";
				break;
			}

		default: $sql = '' === $sql ? 1 : "search GLOB '{$sql}*'"; break;
		}

		$sql = "SELECT city_id, city FROM city WHERE {$sql} ORDER BY OID";

		$a = resolvePath('data/geodb.sqlite');
		$a = new SQLiteDatabase($a);

		$o->cities = new loop_city_($a, $sql, 15);

		return $o;
	}
}

class loop_city_ extends loop
{
	protected $db;
	protected $sql;
	protected $limit;

	protected $prevId;
	protected $count;
	protected $result;

	function __construct($db, $sql, $limit)
	{
		$this->db = $db;
		$this->sql = $sql;
		$this->limit = $limit + 1;
	}

	protected function prepare()
	{
		$this->prevId = 0;
		$this->count = $this->limit;
		$this->result = $this->db->unbufferedQuery($this->sql);

		return -1;
	}

	protected function next()
	{
		if (--$this->count) do
		{
			if ($data = $this->result->fetchObject())
			{
				if ($data->city_id != $this->prevId)
				{
					$this->prevId = $data->city_id;
					return (object) array('city' => $data->city);
				}
			}
			else break;
		}
		while (1);

		unset($this->result);
	}
}

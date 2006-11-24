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

	protected $database = 'mysqli';

	protected $maxage = -1;

	function compose($o)
	{
		$sql = $this->argv->q;
		$sql = ('*' == $sql ? '' : LIB::getKeywords($sql));

		if ($sql)
		{
			$sql = preg_replace("'^st 'u", 'saint ', $sql);

			if (preg_match("'^a[gy]ios( |$)'u", $sql))
			{
				$sql = substr($sql, 5);
				$sql = '(' . $this->doLIKE('search', 'agios' . $sql) . ' OR ' . $this->doLIKE('search', 'ayios' . $sql) . ')';
			}
			else $sql = $this->doLIKE('search', $sql);

			$sql = "SELECT city_id, city FROM geosearch WHERE {$sql} ORDER BY id";
		}
		else
		{
			$sql = "SELECT city_id, city FROM geosearch";
		}

		switch ($this->database)
		{
			case 'sqlite': $o->cities = new loop_cities_sqlite_(new SQLiteDatabase( resolvePath('data/') . 'geodb.sqlite' ), $sql, 15); break;
			case 'mysqli': $o->cities = new loop_cities_mysqli_(DB()->connection, $sql, 15); break;
			case 'mysql' : $o->cities = new loop_cities_mysql_( DB()->connection, $sql, 15); break;
		}

		return $o;
	}

	function doLIKE($field, $a)
	{
		$b = preg_replace("'([a-z0-9])([^a-z0-9]*)$'ie", "chr(1+ord('$1')).'$2'", $a);

		return '' !== $a ? "({$field} >= '{$a}' AND {$field} < '{$b}' AND {$field} LIKE '{$a}%')" : 1;
	}
}

abstract class loop_cities_ extends loop
{
	function __construct($db, $sql, $limit)
	{
		$this->db = $db;
		$this->sql = $sql;
		$this->limit = $limit;
	}

	abstract protected function query();
	abstract protected function fetch();
	abstract protected function free();

	protected function prepare()
	{
		$this->prevId = 0;
		$this->count = 0;

		$this->query();

		return -1;
	}

	protected function next()
	{
		if ($this->count < $this->limit)
		{
			$data = $this->fetch();

			if ($data)
			{
				if ($data->city_id != $this->prevId)
				{
					++$this->count;
					$this->prevId = $data->city_id;

					return (object) array('city' => $data->city);
				}

				return $this->next();
			}
		}

		$this->free();
	}
}

class loop_cities_sqlite_ extends loop_cities_
{
	protected function query() {$this->result = $this->db->unbufferedQuery($this->sql);}
	protected function fetch() {return $this->result->fetchObject();}
	protected function free() {unset($this->result);}
}

class loop_cities_mysqli_ extends loop_cities_
{
	protected function query() {$this->result = $this->db->query($this->sql, MYSQLI_USE_RESULT);}
	protected function fetch() {return $this->result->fetch_object();}
	protected function free() {$this->result->free(); unset($this->result);}
}

class loop_cities_mysql_ extends loop_cities_
{
	protected function query() {$this->result = mysql_unbuffered_query($this->sql, $this->db);}
	protected function fetch() {return mysql_fetch_object($this->result);}
	protected function free() {mysql_free_result($this->result); unset($this->result);}
}

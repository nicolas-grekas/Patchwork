<?php

class
{
	static $db;

	static function __constructStatic()
	{
		self::$db = new SQLiteDatabase(patchworkPath('data/geodb.sqlite'));
	}

	static function getCityId($city)
	{
		$sql = sqlite_escape_string(lingua::getKeywords($city));
		$sql = "SELECT city_id
				FROM city
				WHERE search='{$sql}'
				LIMIT 1";
		$sql = self::$db->arrayQuery($sql, SQLITE_NUM);
		return $sql ? $sql[0][0] : 0;
	}

	static function getCityInfo($city_id)
	{
		$sql = "SELECT c.city_id AS city_id,
						city,
						latitude,
						longitude,
						country,
						div1,
						div2,
						r.zipcode AS divcode
				FROM city c JOIN region r
					ON r.region_id=c.region_id
				WHERE city_id={$city_id}
				LIMIT 1";
		$sql = self::$db->arrayQuery($sql, SQLITE_ASSOC);

		return $sql ? $sql[0] : false;
	}
}

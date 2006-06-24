<?php

class driver_translate_MDB2 extends TRANSLATE
{
	protected $db;
	protected $table = 's_translate';

	public function __construct()
	{
		$this->db = DB();
	}

	public function search($string, $lang)
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

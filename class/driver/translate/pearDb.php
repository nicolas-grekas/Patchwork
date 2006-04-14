<?php

class driver_translate_pearDb extends TRANSLATE
{
	protected $db;
	protected $table = 'sys_translate';

	public function __construct()
	{
		$this->db = DB();
	}

	public function translate($string, $lang)
	{
		$sql = "SELECT {$lang} FROM {$this->table} WHERE __=?";
		$result = $this->db->query($sql, array($string));
		if ($row = $result->fetchRow())
		{
			return $row->$lang;
		}
		else
		{
			$sql = "INSERT INTO {$this->table} (__) VALUES (?)";
			$this->db->query($sql, array($string));
			return '';
		}
	}
}

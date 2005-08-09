<?php

class driver_translate_pearDb extends TRANSLATE
{
	protected $db;
	protected $table = 'sys_translate';

	public function __construct()
	{
		$this->db = DB();
	}

	public function translate($string)
	{
		$quoted_string = $this->db->quote($string);

		$sql = 'SELECT ' . TRANSLATE::$lang . " FROM {$this->table} WHERE " . TRANSLATE::$defaultLang . "={$quoted_string}";
		$result = $this->db->query($sql);
		if ($row = $result->fetchRow())
		{
			return $row->{TRANSLATE::$lang};
		}
		else
		{
			$sql = "INSERT INTO {$this->table} (" . TRANSLATE::$defaultLang . ") VALUES ({$quoted_string})";
			$this->db->query($sql);
			return false;
		}
	}
}

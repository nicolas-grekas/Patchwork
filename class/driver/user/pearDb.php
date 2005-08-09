<?php

class driver_user_pearDb extends USER
{
	protected $db;
	protected $table = 'sys_user';
	protected $userIdField = 'user_id';

	protected $row;

	public function __construct()
	{
		$this->db = DB();
	}

	public function loadPref()
	{
		$user_id = $this->db->quote($this->userId);
		$sql = "SELECT * FROM {$this->table} WHERE {$this->userIdField}={$user_id}";
		$result = $this->db->query($sql);
		if ($row = $result->fetchRow())
		{
			$result->free();
			$this->row = $row;
		}
		else
		{
			$sql = "INSERT INTO {$this->table} ({$this->userIdField}) VALUES ({$user_id})";
			$this->db->query($sql);
			$row = $this->loadPref();
		}

		return $row;
	}

	public function set($prefName, $prefValue);
	{
		if (!isset($this->row->$prefName))
		{
			$sql = "ALTER TABLE {$this->table} ADD {$prefName} varchar(255)";
			$this->db->query($sql);
		}
		
		$sql = "UPDATE {$this->table} SET {$prefName}='' WHERE {$this->userIdField}={$user_id}";
		$this->db->query($sql);
	}

	public function del($prefName);
	{
		$user_id = $this->db->quote($this->userId);
		$sql = "UPDATE {$this->table} SET {$prefName}='' WHERE {$this->userIdField}={$user_id}";
		$this->db->query($sql);
	}

	public function close();
}

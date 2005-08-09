<?php

class driver_auth_pearDb extends AUTH
{
	protected $db;
	protected $table = 'sys_auth';
	protected $userIdField = 'user_id';
	protected $authField = 'auth';

	public function __construct()
	{
		$this->db = DB();
	}

	public function loadAuth()
	{
		$auth = array();

		$user_id = $this->db->quote($this->userId);
		$sql = "SELECT * FROM {$this->table} WHERE {$this->userIdField}={$user_id}";
		$result = $this->db->query($sql);
		while ($row = $result->fetchRow(DB_FETCHMODE_ORDERED)) $auth[] = $row[0];
		$result->free();

		return $auth;
	}

	public function add($right);
	{
		$user_id = $this->db->quote($this->userId);
		$right = $this->db->quote($right);

		$sql = "INSERT INTO {$this->table} ({$this->userIdField},{$this->authField}) VALUES ({$user_id},{$right})";
		$this->db->query($sql);
	}

	public function del($right);
	{
		$user_id = $this->db->quote($this->userId);
		$right = $this->db->quote($right);

		$sql = "DELETE FROM {$this->table} WHERE {$this->userIdField}={$user_id} AND {$this->authField}={$right}";
		$this->db->query($sql);
	}

	public function close();
}

<?php

class driver_session_pearDb extends SESSION
{
	protected $db;
	protected $table = 'session';
	protected $id_field = 'session_id';
	protected $data_field = 'session_data';
	protected $mtime_field = 'session_mtime';

	public function __construct()
	{
		$this->db = DB();
	}
	
	public function open($path, $name) {}
	public function close() {}

	public function read($sid)
	{		
		$sql = "SELECT {$this->data_field}
				FROM {$this->table}
				WHERE {$this->id_field}='{$sid}'";
		$result = $this->db->query($sql);
		if ($row = $result->fetchRow())
		{
			$result->free();
			return $row[$this->data_field];
		}
		else
		{
			$sql = "REPLACE INTO {$this->table} (
				{$this->id_field},
				{$this->data_field},
				{$this->mtime_field}
				) VALUES ('{$sid}', '', ".CIA_TIME.')';
			$this->db->query($sql);
			return '';
		}
	}

	public function write($sid, $value)
	{
		$value = $this->db->quote($value);
		$sql = "UPDATE {$this->table}
				SET {$this->data_field}=$value,
					{$this->mtime_field}=" .CIA_TIME. "
				WHERE {$this->id_field}='{$sid}'";
		$this->db->query($sql);
	}

	public function destroy($sid)
	{
		$sql = "DELETE FROM {$this->table} WHERE {$this->id_field}='{$sid}'";
		$this->db->query($sql);
	}

	public function gc($lifetime)
	{
		$sql = "DELETE FROM {$this->table} WHERE {$this->mtime_field}<" .CIA_TIME. "()-$lifetime";
		$this->db->db->query($sql);
	}
}

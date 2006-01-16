<?php

class loop_sql_mysqli extends loop
{
	protected $db;
	protected $sql;
	protected $result;
	protected $from = 0;
	protected $count = 0;

	public function __construct($sql, $renderer = '', $from = 0, $count = 0)
	{
		$this->db = DB()->connection;
		$this->sql = $sql;
		$this->from = (int) $from;
		$this->count = (int) $count;
		$this->addRenderer($renderer);
	}

	public function setLimit($from, $count)
	{
		$this->from  = (int) $from;
		$this->count = (int) $count;
	}

	protected function prepare()
	{
		$sql = $this->sql;
		if ($this->count > 0) $sql .= " LIMIT {$this->from},{$this->count}";

		$this->result = $this->db->query($sql);

		return $this->result ? $this->result->num_rows : false;
	}

	protected function next()
	{
		if ($row = $this->result->fetch_object()) return $row;
		else $this->result->free();
	}
}

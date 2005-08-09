<?php

class loop_sql_pearDB extends loop
{
	protected $db;
	protected $sql;
	protected $result;
	protected $from = 0;
	protected $count = 0;

	public function __construct($sql, $renderer = '', $from = 0, $count = 0)
	{
		$this->db = DB();
		$this->sql = $sql;
		$this->from = $from;
		$this->count = $count;
		$this->addRenderer($renderer);
	}

	protected function prepare()
	{
		if ($this->count > 0) $this->result = $this->db->limitQuery($this->sql, $this->from, $this->count);
		else $this->result = $this->db->query($this->sql);

		return PEAR::isError($this->result) ? false : $this->result->numRows();
	}

	protected function next()
	{
		if ($row = $this->result->fetchRow()) return $row;
		else $this->result->free();
	}
}

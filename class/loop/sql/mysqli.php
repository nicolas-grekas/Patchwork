<?php

class extends loop
{
	protected $db;
	protected $sql;
	protected $result = false;
	protected $from = 0;
	protected $count = 0;

	function __construct($sql, $filter = '', $from = 0, $count = 0)
	{
		$this->db = DB()->connection;
		$this->sql = $sql;
		$this->from = (int) $from;
		$this->count = (int) $count;
		$this->addFilter($filter);
	}

	function setLimit($from, $count)
	{
		$this->from  = (int) $from;
		$this->count = (int) $count;
	}

	protected function prepare()
	{
		if (!$this->result)
		{
			$sql = $this->sql;
			if ($this->count > 0) $sql .= " LIMIT {$this->from},{$this->count}";

			$this->result = $this->db->query($sql);

			if (!$this->result) E("MySQL Error ({$sql}) : {$this->db->error}");
		}

		return $this->result ? $this->result->num_rows : false;
	}

	protected function next()
	{
		$a = $this->result->fetch_object();

		if ($a) return $a;
		else $this->result->data_seek(0);
	}
}

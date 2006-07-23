<?php // vim: set enc=utf-8 ai noet ts=4 sw=4 fdm=marker:

class extends loop
{
	protected $db;
	protected $sql;
	protected $result;
	protected $from = 0;
	protected $count = 0;

	function __construct($sql, $filter = '', $from = 0, $count = 0)
	{
		$this->db = DB();
		$this->sql = $sql;
		$this->from = $from;
		$this->count = $count;
		$this->addFilter($filter);
	}

	function setLimit($from, $count)
	{
		$this->from = $from;
		$this->count = $count;
	}

	protected function prepare()
	{
		$sql = $this->sql;

		if ($this->count > 0)
		{
			if ('mysql' == $this->db->phptype) $sql .= " LIMIT {$this->from},{$this->count}";
			else $this->db->setLimit($this->count, $this->from);
		}

		$this->result = $this->db->query($sql);

		return @PEAR::isError($this->result) ? false : $this->result->numRows();
	}

	protected function next()
	{
		$a = $this->result->fetchRow();

		if ($a) return $a;
		else $this->result->free();
	}
}

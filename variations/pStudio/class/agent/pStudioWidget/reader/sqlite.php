<?php

class extends agent_pStudioWidget_reader
{
	function control()
	{
		$this->get->__0__ = 'sqlite/' . $this->get->__0__;

		parent::control();
	}

	function compose($o)
	{
		$db = new SQLiteDatabase($this->realpath, 0444, $o->error_msg);

		$sql = "SELECT name, type FROM sqlite_master
			WHERE type IN ('table', 'view')
			ORDER BY name";
		$tables = $db->arrayQuery($sql, SQLITE_ASSOC);

		E($tables);

		$o->tables = new loop_array($tables, 'filter_rawArray');

		return $o;
	}
}

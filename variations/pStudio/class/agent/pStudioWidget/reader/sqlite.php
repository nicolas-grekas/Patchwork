<?php

class extends agent_pStudioWidget_reader
{
	function compose($o)
	{
		$db = new SQLiteDatabase($this->realpath, 0666, $o->error_msg);

		$sql = "SELECT name, type
			FROM sqlite_master
			WHERE type IN ('table', 'view')
			ORDER BY name";
		$tables = $db->arrayQuery($sql, SQLITE_ASSOC);

		$o->tables = new loop_array($tables, 'filter_rawArray');

		return $o;
	}
}

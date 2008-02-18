<?php

class extends agent_pStudioWidget_reader
{
	public $get = array(
		'path:c',
		'low:i' => false,
		'high:i' => PATCHWORK_PATH_LEVEL,
		'sql:t',
		'start:i:0' => 0,
		'length:i:1' => 25,
	);

	function compose($o)
	{
		$db = new SQLiteDatabase($this->realpath, 0666, $o->error_msg);

		if (!$this->get->sql)
		{
			$sql = "SELECT name, type
				FROM sqlite_master
				WHERE type IN ('table', 'view')
				ORDER BY name";
			$tables = $db->arrayQuery($sql, SQLITE_ASSOC);

			$o->tables = new loop_array($tables, 'filter_rawArray');
		}
		else
		{
			// TODO:
			// - security considerations,
			// - better LIMIT handling
			$sql = "{$this->get->sql} LIMIT {$this->get->start}, {$this->get->length}";

			if ($rows = $db->arrayQuery($sql, SQLITE_ASSOC))
			{
				$o->fields = new loop_array(array_keys($rows[0]));
				$o->rows = new loop_array($rows, array($this, 'filterRow'));
				$o->start = $this->get->start;
				$o->length = $this->get->length;
			}
		}

		return $o;
	}

	function filterRow($o)
	{
		$o = (object) array(
			'columns' => new loop_array($o->VALUE),
		);

		return $o;
	}
}

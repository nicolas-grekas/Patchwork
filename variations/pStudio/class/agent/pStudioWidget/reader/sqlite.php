<?php

class extends agent_pStudioWidget_reader
{
	public $get = array(
		'__0__:c',
		'low:i' => false,
		'high:i' => PATCHWORK_PATH_LEVEL,
		'$serverside:b',
		'table:c',
	);

	function compose($o)
	{
		$db = new SQLiteDatabase($this->realpath, 0666, $o->error_msg);
E($this->get->table);
		if(!$this->get->table)
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
			$sql = "SELECT *
				FROM {$this->get->table}
				LIMIT 1";
			$res = $db->unbufferedQuery($sql);
			$n_fields = $res->numFields();
			$i = 0;
			while ($i < $n_fields)
			{
				$fields[] = $res->fieldName($i++);

			}
E($fields);
			$o->field = new loop_array($fields);
		}

		return $o;
	}
}

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

		if ($this->get->sql)
		{
			$a = new geshi(trim($this->get->sql), 'sql');
			$a->set_encoding('UTF-8');
			$o->sql = $a->parse_code();

			if (self::isReadOnlyQuery($db, $this->get->sql, $o->error_msg))
			{
				$sql = "{$this->get->sql}\n LIMIT {$this->get->start}, {$this->get->length}";
				$rows = @$db->arrayQuery($sql, SQLITE_ASSOC);

				if (false !== $rows)
				{
					$o->fields = new loop_array(array_keys($rows[0]));
					$o->rows = new loop_array($rows, array($this, 'filterRow'));
					$o->start = $this->get->start;
					$o->length = $this->get->length;
				}
				else $o->error_msg = sqlite_error_string($db->lastError());
			}
		}
		else
		{
			$sql = "SELECT name, type
				FROM sqlite_master
				WHERE type IN ('table', 'view')
				ORDER BY name";
			$tables = $db->arrayQuery($sql, SQLITE_ASSOC);
			$o->tables = new loop_array($tables, 'filter_rawArray');
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


	protected static function isReadOnlyQuery($db, $sql, &$error_msg)
	{
		$sql = str_replace(';', ';EXPLAIN ', $sql);

		return $db->queryExec("EXPLAIN {$sql}", $error_msg) && $db->queryExec("EXPLAIN SELECT 1 FROM ({$sql}\n)", $error_msg);
	}
}

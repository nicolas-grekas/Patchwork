<?php

class extends agent_pStudioWidget_reader
{
	public

	$get = array(
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
			// - security considerations,	=>	(pStudio::isAuthQuery($this->get->path, $db, $sql)
			// - better LIMIT handling		=>	limit for select request only
			$sql_explode = explode(";", $this->get->sql);
			if (count($sql_explode)>1) $sql = "{$sql_explode[0]}";
			else $sql = "{$this->get->sql}";

			if ($this->isReadOnlyQuery($this->get->path, $db, $sql))
			{
				if (preg_match('/\bselect\b/i', $sql) && !preg_match('/\blimit\b/i', $sql)) $sql = $sql . " LIMIT {$this->get->start}, {$this->get->length}";

				if ($rows = @$db->arrayQuery($sql, SQLITE_ASSOC))
				{
					$o->fields = new loop_array(array_keys($rows[0]));
					$o->rows = new loop_array($rows, array($this, 'filterRow'));
					$o->start = $this->get->start;
					$o->length = $this->get->length;
				}
				if ($db->lastError()) $o->errorSQLite = sqlite_error_string($db->lastError());

				$a = new geshi($this->get->sql, 'sql');
				$a->set_encoding('UTF-8');
				$o->query = $a->parse_code($sql);
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


	public function isReadOnlyQuery($path, $db, $sql)
	{
		$explain = @$db->arrayQuery("EXPLAIN " . $sql, SQLITE_ASSOC);
		if (!is_array($explain)) return false;

		foreach ($explain as $rx)
		{
			if($rx['opcode'] == 'OpenWrite') return false;
		}

		return true;
	}
}

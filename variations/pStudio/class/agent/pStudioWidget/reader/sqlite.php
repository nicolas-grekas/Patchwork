<?php

class extends agent_pStudioWidget_reader
{
	public $get = array(
		'path:c',
		'low:i' => false,
		'high:i' => PATCHWORK_PATH_LEVEL,
		'table:c',
		'start:i:0' => 0,
		'length:i:1' => 25,
		'q:c',
	);

	function compose($o)
	{
		$db = new SQLiteDatabase($this->realpath, 0666, $o->error_msg);

		if (!$this->get->table)
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
			$table = strtr($this->get->table, '[]', '  ');

			$sql = "SELECT * FROM [{$table}] LIMIT {$this->get->start}, {$this->get->length}";
			if ($rows = $db->arrayQuery($sql, SQLITE_ASSOC))
			{
				$o->fields = new loop_array(array_keys($rows[0]));
				$o->rows = new loop_array($rows, array($this, 'filterRow'));
				$o->start = $this->get->start;
				$o->length = $this->get->length;
			}
		}

        // Définition du formulaire de recherche
        $form = new pForm($o);

        $form->add('textarea', 'q');
		$send=$form->add('submit','send');
		$send->add('q', '', '');

		if ($send->isOn())
		{
			$data = $send->getData();
			$sql = sqlite_escape_string($data['q']);

E($sql);
			if ($rows = $db->arrayQuery($sql, SQLITE_ASSOC))
			{
E($rows);
				$o->results = new loop_array($rows, 'filter_rawArray');
				$o->fields = new loop_array(array_keys($rows[0]));
				$o->rows = new loop_array($rows, array($this, 'filterRow'));
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

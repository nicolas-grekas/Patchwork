<?php

class agent_QJsrs_multiSave extends agent_QJsrs
{
	public $argv = array('tabId');

	public function compose()
	{
		$tabId = (int) $this->argv->tabId;

		$data = explode("\t", @$_POST['D']);
		$lockId = array_shift($data);

		$db = DB();
		$len = count($data);

		$db->query('BEGIN');

		for ($i = 0; $i < $len; $i+=3)
		{
			$row = (int) $data[$i];
			$col = (int) $data[$i+1];
			$value = $data[$i+2];

			$sql = "SELECT lockId FROM data WHERE tabId={$tabId} AND row={$row} AND col={$col} ORDER BY row, col";
			if ($a = $db->getRow($sql))
			{
				if (!$a->lockId || $a->lockId==$lockId)
				{
					$a = $db->autoExecute(
						'data',
						array(
							'data' => $value,
							'lockId' => 0,
							'version' => $lockId,
						),
						DB_AUTOQUERY_UPDATE,
						"tabId={$tabId} AND row={$row} AND col={$col}"
					);

					if (!($a && $db->affectedRows())) $lockId = 0;
				}
				else $lockId = 0;
			}
			else
			{
				$a = $db->autoExecute(
					'data',
					array(
						'tabId' => $tabId,
						'row' => $row,
						'col' => $col,
						'data' => $value,
						'lockId' => 0,
						'version' => $lockId
					),
					DB_AUTOQUERY_INSERT
				);

				if (!$a) $lockId = 0;
			}

			if (!$lockId)
			{
				$db->query('ROLLBACK');
				return parent::compose();
			}
		}

		$db->query('COMMIT');

		$this->data['completed'] = 1;

		return parent::compose();
	}
}

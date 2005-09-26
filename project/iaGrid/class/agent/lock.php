<?php

class agent_lock extends agent_bin
{
	public $argv = array('tabId', 'R', 'C');

	public function render()
	{
		$tabId = (int) $this->argv->tabId;

		$row = (int) $this->argv->R;
		$col = (int) $this->argv->C;
		$lockId = 0;

		$db = DB();

		$sql = "SELECT lockId FROM data WHERE tabId={$tabId} AND row={$row} AND col={$col}";
		if ($a = $db->getRow($sql))
		{
			if (!$a->lockId)
			{
				$lockId = $db->nextId('lockId');
				$a = $db->autoExecute(
					'data',
					array(
						'lockId' => $lockId,
					),
					DB_AUTOQUERY_UPDATE,
					"tabId={$tabId} AND row={$row} AND col={$col} AND lockId=0"
				);

				if (!($a && $db->affectedRows())) $lockId = 0;
			}
		}
		else
		{
			$lockId = $db->nextId('lockId');
			$a = $db->autoExecute(
				'data',
				array(
					'tabId' => $tabId,
					'row' => $row,
					'col' => $col,
					'data' => '',
					'lockId' => $lockId,
					'version' => 0
				),
				DB_AUTOQUERY_INSERT
			);

			if (!$a) $lockId = 0;
		}

		if ($lockId)
		{
			CIA::cancel();

			$sleep = 500;	// (ms)
	
			apache_setenv('no-gzip', '1');
			ignore_user_abort(false);
			set_time_limit(0);

			register_shutdown_function(array($this, 'release'), $tabId, $row, $col);

			echo "<script>parent.openEdit({$lockId})</script>\n";
			echo str_repeat("\n", 512);

			while (1)
			{
				echo "\n";
				flush();
				usleep($sleep);
			}
		}

		return (object) array('DATA' => "<script>parent.releaseEdit(1)</script>\n");
	}

	public function release($tabId, $row, $col)
	{
		$sql = "UPDATE data SET lockId=0 WHERE tabId={$tabId} AND row={$row} AND col={$col}";
		DB()->query($sql);
	}
}

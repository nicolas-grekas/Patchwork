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

		$sql = "SELECT data, lockId FROM data WHERE tabId={$tabId} AND row={$row} AND col={$col}";
		if ($data = $db->getRow($sql))
		{
			if (!$data->lockId)
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

			$data = $data->data;
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

			$data = '';
		}

		if ($lockId)
		{
			CIA::cancel();

			$sleep = 500;	// (ms)
			$repeated = str_repeat("\n", 4*4096);
	
			apache_setenv('no-gzip', '1');
			ignore_user_abort(false);
			set_time_limit(600);

			header('Content-Type: text/html; charset=UTF-8');
			header('Cache-Control: max-age=0,private,must-revalidate');

			register_shutdown_function(array($this, 'release'), $tabId, $row, $col);

			echo "<script>parent.openEdit({$lockId},'",
				str_replace(
					array("\r\n", "\r", '\\'  , "\n", "'"),
					array("\n"  , "\n", '\\\\', '\n', "\\'"),
					$data
				),
				"')</script>";

			while (1)
			{
				echo $repeated;
				flush();
				usleep(1000*$sleep);
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

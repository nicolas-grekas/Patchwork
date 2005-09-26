<?php

class agent_QJsrs_save extends agent_QJsrs
{
	public $argv = array('tabId');

	public function render()
	{
		$tabId = (int) $this->argv->tabId;

		$lockId = (int) @$_POST['L'];
		$row = (int) @$_POST['R'];
		$col = (int) @$_POST['C'];
		$data = trim(@$_POST['D']);

		DB()->autoExecute(
			'data',
			array(
				'data' => $data,
				'lockId' => 0,
				'version' => $lockId,
			),
			DB_AUTOQUERY_UPDATE,
			"tabId={$tabId} AND row={$row} AND col={$col}"
		);

		return parent::render();
	}
}

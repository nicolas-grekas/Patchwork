<?php

class agent_QJsrs_update extends agent_QJsrs
{
	public $argv = array('tabId');

	public function compose()
	{
		$tabId = (int) $this->argv->tabId;

		$version = (int) @$_POST['L'];

		$db = DB();

		$this->data = array(
			'version' => $db->getRow("SELECT MAX(version) AS version FROM data WHERE tabId={$tabId}")->version,
			'rows' => $db->getAll("SELECT row AS R, col AS C, data AS D FROM data WHERE tabId={$tabId} AND version>{$version} ORDER BY row, col"),
			'locked' => $db->getAll("SELECT row AS R, col AS C FROM data WHERE tabId={$tabId} AND lockId!=0 ORDER BY row, col"),
		);

		return parent::compose();
	}
}

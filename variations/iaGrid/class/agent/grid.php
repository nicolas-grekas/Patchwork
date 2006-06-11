<?php

class agent_grid extends agent
{
	public $argv = array('__1__');

	public function compose()
	{
		$db = DB();

		$docId = (int) $this->argv->__1__;

		$sql = "SELECT d.label, t.tabId FROM doc d, tab t WHERE d.docId={$docId} AND t.docId=d.docId ORDER BY tabId LIMIT 1";
		$data = $db->getRow($sql);

		$data->tab = new loop_sql("SELECT tabId, label FROM tab WHERE docId={$docId} ORDER BY label");

		return $data;
	}
}

<?php

class agent_QJsrs_upload extends agent_QJsrs
{
	public $argv = array('id');

	public function compose()
	{
			$this->data = $this->argv->id && is_callable('upload_progress_meter_get_info')
			? (object) @upload_progress_meter_get_info($this->argv->id)
			: (object) array();

			return parent::compose();
	}

	public function metaCompose()
	{
		if ($this->argv->id)
		{
			$this->expires = 'onmaxage';
			CIA::setGroup('private');
		}
		else $this->maxage = CIA_MAXAGE;

		return parent::metaCompose();
	}
}

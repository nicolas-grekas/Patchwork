<?php

class agent_QJsrs_upload extends agent_QJsrs
{
	public $argv = array('id');

	public function getMeta()
	{
		if ($this->argv->id)
		{
			$this->maxage = 0;
			$this->expires = 'onmaxage';
			$this->private = true;
		}
		else
		{
			$this->maxage = CIA_MAXAGE;
			$this->expires = 'ontouch';
			$this->private = false;
		}

		return parent::getMeta();
	}

	public function render()
	{
			$this->data = $this->argv->id && is_callable('upload_progress_meter_get_info')
			? (object) @upload_progress_meter_get_info($this->argv->id)
			: (object) array();

			return parent::render();
	}
}

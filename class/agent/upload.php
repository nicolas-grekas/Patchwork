<?php

class agent_upload extends agent
{
	public $argv = array('id');

	public function compose()
	{
		$a = (object) array();

		if ($this->argv->id)
		{
			$this->expires = 'onmaxage';
			CIA::setGroup('private');

			if (is_callable('upload_progress_meter_get_info'))
			{
				$a = (object) @upload_progress_meter_get_info($this->argv->id);
			}
		}
		else $this->maxage = -1;

		return $a;
	}
}

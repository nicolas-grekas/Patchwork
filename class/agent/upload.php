<?php

class extends agent
{
	public $argv = array('id');

	function compose($o)
	{
		if ($this->argv->id)
		{
			$this->expires = 'onmaxage';
			CIA::setGroup('private');

			if (is_callable('upload_progress_meter_get_info'))
			{
				$o = (object) @upload_progress_meter_get_info($this->argv->id);
			}
		}
		else $this->maxage = -1;

		return $o;
	}
}

<?php

class extends agent_pStudio
{
	public $get = array(
		'__0__:c',
		'low:i',
		'high:i'
	);


	function control()
	{
		$this->get->__0__ = pStudio::decFilename($this->get->__0__);

		parent::control();
	}

	function compose($o)
	{
		$o->appname = pStudio::getAppname($this->depth);
		$o->paths = new loop_array(explode('/', '/' === substr($this->path, -1) ? substr($this->path, 0, -1) : $this->path));

		return $o;
	}
}

<?php

class agent_dispatch extends agent
{
	public $argv = array('src');

	protected $maxage = -1;

	public function render()
	{
		return (object) array(
			'src' => $this->argv->src
		);
	}
}

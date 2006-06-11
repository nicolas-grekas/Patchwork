<?php

class agent_QSelect extends agent_bin
{
	protected $template = 'QSelect/Search.js';

	public function control()
	{
		parent::control();
		CIA::header('Content-Type: text/javascript; charset=UTF-8');
	}
}

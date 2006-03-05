<?php

class agent_QSelect extends agent_bin
{
	protected $template = 'QSelect/Search.js';

	public function compose()
	{
		CIA::header('Content-Type: text/javascript; charset=UTF-8');

		return parent::compose();
	}
}

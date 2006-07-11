<?php

class extends iaForm_submit
{
	protected $type = 'image';

	protected function init(&$param)
	{
		unset($this->form->rawValues[$this->name]);
		parent::init($param);
	}
}

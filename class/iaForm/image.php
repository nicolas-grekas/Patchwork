<?php // vim: set enc=utf-8 ai noet ts=4 sw=4 fdm=marker:

class extends iaForm_submit
{
	protected $type = 'image';

	protected function init(&$param)
	{
		unset($this->form->rawValues[$this->name]);
		parent::init($param);
	}
}

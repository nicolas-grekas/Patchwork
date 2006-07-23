<?php // vim: set enc=utf-8 ai noet ts=4 sw=4 fdm=marker:

class extends iaForm_text
{
	protected $type = 'password';

	protected function get()
	{
		$this->value = '';
		return parent::get();
	}
}

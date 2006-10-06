<?php

class extends iaForm_text
{
	protected $type = 'password';

	protected function get()
	{
		$this->value = '';
		return parent::get();
	}
}

<?php

class iaForm_QSelect extends iaForm_text
{
	protected $src;

	protected function init(&$param)
	{
		parent::init($param);
		if (isset($param['src'])) $this->src = $param['src'];
	}

	protected function get()
	{
		$a = parent::get();
		if (isset($this->src)) $a->_src_ = $this->src;
		return $a;
	}
}

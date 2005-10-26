<?php

class iaForm_QSelect extends iaForm_text
{
	protected $src;

	protected function init(&$param)
	{
		parent::init($param);
		if (isset($param['src'])) $this->src = $param['src'];
		if (isset($param['lock']) && $param['lock']) $this->lock = 1;
	}

	protected function get()
	{
		$a = parent::get();

		$this->agent = 'QSelect/input';
		$this->keys = array();

		if (isset($this->src)) $a->_src_ = $this->src;
		if (isset($this->lock)) $a->_lock_ = $this->lock;

		return $a;
	}
}

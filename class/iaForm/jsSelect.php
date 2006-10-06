<?php

class extends iaForm_select
{
	protected $src;

	protected function init(&$param)
	{
		unset($param['item']);
		unset($param['sql']);
		if (!isset($param['valid'])) $param['valid'] = 'string';

		parent::init($param);

		if (isset($param['src'])) $this->src = $param['src'];
	}

	protected function get()
	{
		$a = parent::get();

		$this->agent = 'form/jsSelect';

		if (isset($this->src)) $a->_src_ = $this->src;

		if ($this->status) $a->_value = new loop_array((array) $this->value, false);

		unset($a->_type);

		return $a;
	}
}

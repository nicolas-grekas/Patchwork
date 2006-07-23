<?php

class extends iaForm_hidden
{
	protected $type = 'text';
	protected $maxlength = 255;

	protected function init(&$param)
	{
		parent::init($param);
		if (@$param['maxlength'] > 0) $this->maxlength = (int) $param['maxlength'];
		if (mb_strlen($this->value) > $this->maxlength) $this->value = mb_substr($this->value, 0, $this->maxlength);
	}

	protected function get()
	{
		$a = parent::get();
		if ($this->maxlength) $a->maxlength = $this->maxlength;
		return $a;
	}

	protected function addJsValidation($a)
	{
		$a->_valid = new loop_array(array_merge(array($this->valid), $this->valid_args));
		return $a;
	}
}

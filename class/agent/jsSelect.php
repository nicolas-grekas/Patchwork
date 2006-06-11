<?php

class agent_jsSelect extends agent_bin
{
	protected $maxage = -1;
	protected $template = 'form/jsSelect.js';

	protected $param = array();

	public function control()
	{
		CIA::header('Content-Type: text/javascript; charset=UTF-8');
	}

	public function compose()
	{
		unset($this->param['valid']);
		unset($this->param['firstItem']);
		unset($this->param['multiple']);

		$a = (object) array();

		$this->form = new iaForm($a, '', true, '');
		$this->form->add('select', 'select', $this->param);

		return $a;
	}
}

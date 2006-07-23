<?php // vim: set enc=utf-8 ai noet ts=4 sw=4 fdm=marker:

class extends iaForm_hidden
{
	protected $type = 'submit';
	public $isdata = false;

	protected function init(&$param)
	{
		if (isset($this->form->rawValues[$this->name])) $this->status = true;
		else if (isset($this->form->rawValues[$this->name.'_x']) && isset($this->form->rawValues[$this->name.'_y']))
		{
			$this->value = array(@$this->form->rawValues[$this->name.'_x'], @$this->form->rawValues[$this->name.'_y']);

			$x = VALIDATE::get($this->value[0], 'int');
			$y = VALIDATE::get($this->value[1], 'int');

			$this->status = $x!==false && $y!==false;
			$this->value = $this->status ? array($x, $y) : array();
		}
		else $this->status = '';

		$this->form->setEnterControl($this->name);
	}

	protected function get()
	{
		$a = parent::get();
		unset($a->value);
		return $a;
	}
}

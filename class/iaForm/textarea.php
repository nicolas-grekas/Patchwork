<?php

class extends iaForm_text
{
	protected $type = 'textarea';
	protected $maxlength = 65535;

	protected function get()
	{
		$a = parent::get();
		unset($a->maxlength);

		if ($this->maxlength)
		{
			$a->onkeyup = 'if(this.value.length>'.$this->maxlength.')this.value=this.value.substr(0,'.$this->maxlength.')';
		}

		return $a;
	}
}

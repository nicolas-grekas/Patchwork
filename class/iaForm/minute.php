<?php

class extends iaForm_text
{
	protected $maxlength = 2;
	protected $maxint = 59;

	protected function get()
	{
		$a = parent::get();
		$a->onchange = "this.value=this.value/1||'';if(this.value<0||this.value>{$this->maxint})this.value=''";
		return $a;
	}
}

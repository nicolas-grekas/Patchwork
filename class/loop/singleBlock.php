<?php

class loop_singleBlock extends loop
{
	private $getter;
	private $data;
	private $firstCall = true;

	public function __construct($getter = false)
	{
		$this->getter = $getter;
	}

	final protected function prepare() {return 1;}
	final protected function next()
	{
		if ($this->firstCall)
		{
			$this->firstCall = false;
			if (!isset($this->data)) $this->data = $this->get();
			return $this->data;
		}
		else
		{
			$this->firstCall = true;
			return false;
		}
	}

	protected function get()
	{
		if ($this->getter) return call_user_func($this->getter);
		else return (object) array();
	}
}

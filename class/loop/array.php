<?php

class loop_array extends loop
{
	protected $array;
	protected $isAssociative = true;

	public function __construct($array, $renderer = '', $isAssociative = null)
	{
		$this->array =& $array;
		if ($renderer) $this->addRenderer($renderer);
		$this->isAssociative = $isAssociative!==null ? $isAssociative : $renderer!==false;
	}

	protected function prepare() {return count($this->array);}

	protected function next()
	{
		if (list($key, $value) = each($this->array))
		{
			$data = array('VALUE' => &$value);
			if ($this->isAssociative) $data['KEY'] =& $key;

			return (object) $data;
		}
		else reset($this->array);
	}
}

function render_rawArray($data)
{
	return $data->VALUE;
}

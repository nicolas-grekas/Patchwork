<?php

class loop_array extends loop
{
	protected $array;
	protected $isAssociative = true;

	public function __construct($array, $filter = '', $isAssociative = null)
	{
		$this->array =& $array;
		if ($filter) $this->addFilter($filter);
		$this->isAssociative = $isAssociative!==null ? $isAssociative : $filter!==false;
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

function filter_rawArray($data)
{
	return $data->VALUE;
}

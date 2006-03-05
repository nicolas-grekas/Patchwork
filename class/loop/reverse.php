<?php

class loop_reverse extends loop_array
{
	public function __construct($loop)
	{
		$array = $this->getArray($loop, true);

		parent::__construct($array, 'filter_rawArray');
	}

	function getArray($loop, $unshift = false)
	{
		$array = array();

		while ($a = $loop->compose())
		{
			foreach ($a as $k => $v) if ($v instanceof loop) $a->$k = new loop_array($this->getArray($v), 'filter_rawArray');

			$unshift ? array_unshift($array, $a) : ($array[] = $a);
		}

		return $array;
	}
}

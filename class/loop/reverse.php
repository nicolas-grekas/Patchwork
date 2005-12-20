<?php

class loop_reverse extends loop_array
{
	public function __construct($loop)
	{
		$array = $this->getArray($loop, true);

		parent::__construct($array, 'render_rawArray');
	}

	function getArray($loop, $unshift = false)
	{
		$array = array();

		while ($a = $loop->render())
		{
			foreach ($a as $k => $v) if ($v instanceof loop) $a->$k = new loop_array($this->getArray($v), 'render_rawArray');

			$unshift ? array_unshift($array, $a) : ($array[] = $a);
		}

		return $array;
	}
}

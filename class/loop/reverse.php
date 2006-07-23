<?php // vim: set enc=utf-8 ai noet ts=4 sw=4 fdm=marker:

class extends loop_array
{
	function __construct($loop)
	{
		$array = $this->getArray($loop, true);

		parent::__construct($array, 'filter_rawArray');
	}

	function getArray($loop, $unshift = false)
	{
		$array = array();

		while ($a = $loop->loop())
		{
			foreach ($a as &$v) if ($v instanceof loop) $v = new loop_array($this->getArray($v), 'filter_rawArray');

			$unshift ? array_unshift($array, $a) : ($array[] = $a);
		}

		return $array;
	}
}

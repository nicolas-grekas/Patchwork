<?php

class extends agent
{
	function control()
	{
	}

	function compose($o)
	{
		patchworkPath('zcache/', $o->zcacheDepth);

		$app = array();

		foreach ($GLOBALS['patchwork_path'] as $k => $v)
		{
			pStudio::isAuthApp($v) && $app[$k] = $v;
		}

		$o->apps = new loop_array($app, array($this, 'filterApp'));

		return $o;
	}

	function filterApp($o)
	{
		$depth = PATCHWORK_PATH_LEVEL - $o->KEY;

		$o = (object) array(
			'name' => pStudio::getAppname($depth),
			'depth' => $depth,
			'path' => $o->VALUE,
		);

		return $o;
	}
}

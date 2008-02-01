<?php

class extends agent
{
	public $get = array(
		'__0__:c',
		'low:i' => false,
		'high:i' => PATCHWORK_PATH_LEVEL,
	);

	protected $realpath, $path, $depth;

	function control()
	{
		if (false === $this->get->low)
		{
			resolvePath('zcache/');
			$this->get->low = $GLOBALS['patchwork_lastpath_level'];
		}

		$this->setPath($this->get->__0__, $this->get->low, $this->get->high) || p::redirect('pStudio');
	}

	function compose($o)
	{
		$o->filename = pStudio::encFilename($this->path);
		$o->dirname  = '' === $this->path || '/' === substr($this->path, -1) ? $this->path : dirname($this->path);

		$o->low  = $this->get->low;
		$o->high = $this->get->high;

		return $o;
	}

	function setPath($path, $low, $high)
	{
		if ('' === $path)
		{
			if (!isset($GLOBALS['patchwork_path'][PATCHWORK_PATH_LEVEL - $high])) return false;

			$realpath = $GLOBALS['patchwork_path'][PATCHWORK_PATH_LEVEL - $high] . '/';
			$depth = $high;
		}
		else
		{
			$realpath = resolvePath($path, $high, 0);
			$depth = $GLOBALS['patchwork_lastpath_level'];

			if (!$realpath || $depth < $low) return false;

			'/' !== substr($path, -1) && is_dir($realpath) && $path .= '/';
		}

		if (pStudio::isAuthRead($path))
		{
			$this->realpath = $realpath;
			$this->path = $path;
			$this->depth = $depth;

			return true;
		}

		return false;
	}
}

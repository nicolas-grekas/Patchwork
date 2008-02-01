<?php

class extends agent
{
	public $get = array(
		'__0__:c',
		'low:i' => false,
		'high:i' => false,
	);

	protected $dirname, $path, $depth;

	function control()
	{
		$this->setPath($this->get->__0__, $this->get->low, $this->get->high) || p::redirect('pStudio');
	}

	function compose($o)
	{
		$o->filename = pStudio::encFilename($this->path);
		$o->dirname  = '' === $this->path || '/' === substr($this->path, -1) ? $this->path : dirname($this->path);

		$o->low  = false !== $this->get->low  ? $this->get->low  : $GLOBALS['patchwork_lastpath_level'];
		$o->high = false !== $this->get->high ? $this->get->high : PATCHWORK_PATH_LEVEL;

		return $o;
	}

	function setPath($path, $low, $high)
	{
		if ('' === $path)
		{
			if (!isset($GLOBALS['patchwork_path'][PATCHWORK_PATH_LEVEL - $high])) return false;

			$dirname = $GLOBALS['patchwork_path'][PATCHWORK_PATH_LEVEL - $high] . '/';
			$path = '';
			$depth = $high;
		}
		else
		{
			$path = resolvePath($path, $high, 0);
			$depth = $GLOBALS['patchwork_lastpath_level'];

			if (!$path || $depth < $low) return false;

			$dirname = $GLOBALS['patchwork_path'][PATCHWORK_PATH_LEVEL - $depth] . '/';
			$path = substr($path, strlen($dirname)) . ('/' !== substr($path, -1) && is_dir($path) ? '/' : '');
			if ($depth < 0) $path = 'class/' . $path;
		}

		if (pStudio::isAuthRead($path))
		{
			$this->dirname = $dirname;
			$this->path = $path;
			$this->depth = $depth;

			return true;
		}

		return false;
	}
}

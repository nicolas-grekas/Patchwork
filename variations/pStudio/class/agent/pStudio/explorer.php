<?php

class extends agent
{
	public $get = array(
		'__0__:c',
		'low:i' => false,
		'high:i' => PATCHWORK_PATH_LEVEL,
		'$serverside:b',
	);

	protected

	$rawContentType = 'application/octet-stream',
	$realpath, $path, $depth, $is_auth_edit = false;


	function control()
	{
		if (false === $this->get->low)
		{
			resolvePath('zcache/');
			$this->get->low = $GLOBALS['patchwork_lastpath_level'];
		}

		$this->setPath($this->get->__0__, $this->get->low, $this->get->high) || p::redirect('pStudio');

		if (isset($this->get->{'$serverside'}) && $this->get->{'$serverside'} && is_file($this->realpath))
		{
			header('Content-Type: ' . $this->rawContentType);
			p::readfile($this->realpath, false);
		}
	}

	function compose($o)
	{
		$o->low  = $this->get->low;
		$o->high = $this->get->high;

		$o->appname = pStudio::getAppname($this->depth);
		$o->is_file = '' !== $this->path && '/' !== substr($this->path, -1);
		$o->is_auth_edit = $this->is_auth_edit;
		$o->topname = '' !== $this->path ? basename($o->is_file ? $this->path : substr($this->path, 0, -1)) : $o->appname;
		$o->dirname = $o->is_file ? dirname($this->path) . '/' : $this->path;
		'./' === $o->dirname && $o->dirname = '';

		$o->paths = new loop_array(explode('/', rtrim($this->path, '/')));
		$o->subpaths = new loop_array($this->getSubpaths($o->dirname, $o->low, $o->high), 'filter_rawArray');

		if ($o->is_auth_edit)
		{
			$f = new pForm($o);
			$f->add('file', 'file');
			$send = $f->add('submit', 'send');
			$send->add('file', 'Please select a file', '');

			if ($send->isOn())
			{
				$file = $f->getElement('file')->getValue();
				unlink($this->realpath);
				move_uploaded_file($file['tmp_name'], $this->realpath);

				p::redirect();
			}

		}
		return $o;
	}

	protected function setPath($path, $low, $high)
	{
		if ('' === $path)
		{
			if (!isset($GLOBALS['patchwork_path'][PATCHWORK_PATH_LEVEL - $high])) return false;

			$realpath = $GLOBALS['patchwork_path'][PATCHWORK_PATH_LEVEL - $high];
			$depth = $high;
		}
		else
		{
			$realpath = resolvePath($path, $high, 0);
			$depth = $GLOBALS['patchwork_lastpath_level'];

			if (!$realpath || $depth < $low) return false;

			'/' !== substr($path, -1) && is_dir($realpath) && $path .= '/';
		}

		$this->is_auth_edit = pStudio::isAuthEdit($path);

		if ($this->is_auth_edit || pStudio::isAuthRead($path))
		{
			$this->realpath = $realpath;
			$this->path = $path;
			$this->depth = $depth;

			return true;
		}

		return false;
	}

	protected function getSubpaths($dirpath, $low, $high)
	{
		global $patchwork_lastpath_level;

		$paths = array();

		$i = $high;
		$isTop = 1;
		do
		{
			if ('' !== $dirpath)
			{
				$path = resolvePath($dirpath, $i, 0);
				$depth = $patchwork_lastpath_level;
			}
			else if ($i < 0)
			{
				if (!pStudio::isAuthRead('class')) break;

				if (isset($paths['class']))
				{
					++$paths['class']['ancestorsNb'];
				}
				else
				{
					$paths['class'] = array(
						'name' => 'class',
						'isTop' => $isTop,
						'isDir' => 1,
						'ancestorsNb' => 0,
						'depth' => $i,
					);
				}

				break;
			}
			else
			{
				$path = $GLOBALS['patchwork_path'][PATCHWORK_PATH_LEVEL - $i];
				$depth = $i;
			}

			if (!$path || $depth < $low) break;

			$h = @opendir($path);
			if (!$h) break;
			while (false !== $file = readdir($h)) if ('.' !== $file && '..' !== $file)
			{
				if (isset($paths[$file]))
				{
					++$paths[$file]['ancestorsNb'];
				}
				else
				{
					$isDir = is_dir($path . $file) - 0;

					if (!pStudio::isAuthRead($dirpath . $file . ($isDir ? '/' : ''))) continue;

					$paths[$file] = array(
						'name' => $file,
						'isTop' => $isTop,
						'isDir' => $isDir,
						'ancestorsNb' => 0,
						'depth' => $i,
						'appname' => pStudio::getAppname($i),
						'isApp' => $isDir && file_exists($path . $file . '/config.patchwork.php'),
					);
				}
			}
			closedir($h);

			--$i;
			$isTop = 0;
		}
		while ($i >= $low);

		usort($paths, array($this, 'pathCmp'));

		return $paths;
	}

	protected function pathCmp($a, $b)
	{
		if ($b['isDir'] - $a['isDir']) return $b['isDir'] - $a['isDir'];

		return strnatcasecmp($a['name'], $b['name']);
	}
}

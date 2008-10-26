<?php

class extends agent
{
	public $get = array(
		'__0__:c',
		'low:i' => 0,
		'high:i' => PATCHWORK_PATH_LEVEL,
		'$serverside:b',
	);

	protected

	$rawContentType = 'application/octet-stream',
	$realpath, $path, $depth, $is_auth_edit = false;


	function control()
	{
		$this->setPath($this->get->__0__, $this->get->low, $this->get->high) || p::redirect('pStudio');

		if (!empty($this->get->{'$serverside'}) && is_file($this->realpath))
		{
			p::readfile($this->realpath, $this->rawContentType, $this->realpath);
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

			if ($o->is_file)
			{
				$f->add('file', 'file');
				$send = $f->add('submit', 'send');
				$send->attach('file', 'Please select a file', '');

				if ($send->isOn())
				{
					$file = $f->getElement('file')->getValue();
					unlink($this->realpath);
					move_uploaded_file($file['tmp_name'], $this->realpath);

					p::redirect();
				}
			}
			else
			{
				$filename = $f->add('text', 'filename', array(
					'valid' => 'c', '^[^\x00-\x1F\/:*?"<>|\x7F]+$',
					'validmsg' => T('Special characters are forbidden:') . ' \ / : * ? " < > |'
				));

				if ($filename->getStatus())
				{
					$newfile = $filename->getValue();
					pStudio::isAuthEdit($this->path . $newfile) || $filename->setError(T('You are not allowed to create a ressource with this name'));
					$filename = $newfile;
				}

				$newfile = $f->add('submit', 'newfile');
				$newfile->attach('filename', 'Please fill in the file name', '');

				$newdir = $f->add('submit', 'newdir');
				$newdir->attach('filename', 'Please fill in the directory name', '');

				     if ($newfile->isOn()) file_put_contents($this->realpath . '/' . $filename, "\n");
				else if ($newdir ->isOn()) mkdir($this->realpath . '/' . $filename);
				else $filename = false;

				if (false !== $filename)
				{
					unlink('./.patchwork.php');
					p::redirect("pStudio/explorer/{$this->path}{$filename}/?low={$this->get->low}&high={$this->get->high}");
				}
			}

			if ('' !== $this->path
				&& ($o->is_file || !$o->subpaths->getLength())
				&& $f->add('submit', 'del')->isOn())
			{
				if ($o->is_file) rename($this->realpath, $this->realpath . '~trashed');
				else rmdir($this->realpath);

				unlink('./.patchwork.php');
				p::redirect('pStudio/explorer/' . dirname($this->path) . "/?low={$this->get->low}&high={$this->get->high}");
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
			$realpath = patchworkPath($path, $depth, $high, 0);

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
		$paths = array();

		$i = $high;
		$isTop = 1;
		do
		{
			if ('' !== $dirpath) $path = patchworkPath($dirpath, $depth, $i, 0);
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

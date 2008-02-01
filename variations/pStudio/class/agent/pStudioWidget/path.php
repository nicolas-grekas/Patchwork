<?php

class extends agent_pStudio
{
	public $get = array(
		'__0__:c',
		'low:i',
		'high:i'
	);

	function compose($o)
	{
		$low  = $this->get->low;
		$high = $this->get->high;

		global $patchwork_lastpath_level;

		$paths = array();

		$i = $high;
		$isTop = 1;
		do
		{
			if ('' !== $this->path)
			{
				$path = resolvePath($this->path, $i, 0);
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
					$isDir = is_dir($path . '/' . $file) - 0;

					if (!pStudio::isAuthRead($this->path . $file . ($isDir ? '/' : ''))) continue;

					$paths[$file] = array(
						'name' => $file,
						'isTop' => $isTop,
						'isDir' => $isDir,
						'ancestorsNb' => 0,
						'depth' => $i,
						'appname' => pStudio::getAppname($i),
						'isApp' => $isDir && file_exists($path . '/' . $file . '/config.patchwork.php'),
					);
				}
			}
			closedir($h);

			--$i;
			$isTop = 0;
		}
		while ($i >= $low);

		usort($paths, array($this, 'pathCmp'));

		$o->paths = new loop_array($paths, 'filter_rawArray');

		return $o;
	}

	function pathCmp($a, $b)
	{
		if ($b['isDir'] - $a['isDir']) return $b['isDir'] - $a['isDir'];

		return strnatcasecmp($a['name'], $b['name']);
	}
}

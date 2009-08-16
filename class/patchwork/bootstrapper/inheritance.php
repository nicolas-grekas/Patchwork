<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class patchwork_bootstrapper_inheritance
{
	static

	$rootPath,
	$configSource = array(),
	$appId;


	static function getLinearizedGraph($root_path, $top_path, &$configSource, &$appId)
	{
		self::$rootPath     = $root_path;
		self::$configSource =& $configSource;
		self::$appId        =& $appId;

		// Linearize applications inheritance graph

		$a = self::c3mro($root_path, $top_path);
		$a = array_slice($a, 1);
		$a[] = $root_path;

		return $a;
	}

	// C3 Method Resolution Order (like in Python 2.3) for multiple application inheritance
	// See http://python.org/2.3/mro.html

	protected static function c3mro($realpath, $top_path = false)
	{
		static $cache = array();

		$resultSeq =& $cache[$realpath];

		// If result is cached, return it
		if (null !== $resultSeq) return $resultSeq;

		$parent = self::getParentApps($realpath);

		// If no parent app, result is trival
		if (!$parent && !$top_path) return $resultSeq = array($realpath);

		if ($top_path) array_unshift($parent, $top_path);

		// Compute C3 MRO
		$seqs = array_merge(
			array(array($realpath)),
			array_map(array(__CLASS__, 'c3mro'), $parent),
			array($parent)
		);
		$resultSeq = array();
		$parent = false;

		while (1)
		{
			if (!$seqs)
			{
				false !== $top_path && $cache = array();
				return $resultSeq;
			}

			unset($seq);
			$notHead = array();
			foreach ($seqs as $seq)
				foreach (array_slice($seq, 1) as $seq)
					$notHead[$seq] = 1;

			foreach ($seqs as &$seq)
			{
				$parent = reset($seq);

				if (isset($notHead[$parent])) $parent = false;
				else break;
			}

			if (false === $parent) die('Patchwork Error: Inconsistent application hierarchy in ' . $realpath . 'config.patchwork.php');

			$resultSeq[] = $parent;

			foreach ($seqs as $k => &$seq)
			{
				if ($parent === current($seq)) unset($seqs[$k][key($seq)]);
				if (!$seqs[$k]) unset($seqs[$k]);
			}
		}
	}

	protected static function getParentApps($realpath)
	{
		$parent = array();
		$config = $realpath . 'config.patchwork.php';


		// Get config's source and clean it

		file_exists($config)
			|| die('Patchwork Error: Missing file ' . $config);

		self::$appId += filemtime($config);

		$source = file_get_contents($config);
		UTF8_BOM === substr($source, 0, 3) && $source = substr($source, 3);
		false !== strpos($source, "\r") && $source = strtr(str_replace("\r\n", "\n", $source), "\r", "\n");
		"\n" === $source && $source = '';

		ob_start();

		if ($source = token_get_all($source))
		{
			$len = count($source);

			if (T_OPEN_TAG == $source[0][0])
			{
				$source[0] = '';

				for ($i = 1; $i < $len; ++$i)
				{
					$a = $source[$i];

					if (is_array($a) && in_array($a[0], array(T_COMMENT, T_WHITESPACE, T_DOC_COMMENT)))
					{
						if (T_COMMENT == $a[0] && preg_match('/^#patchwork[ \t]/', $a[1])) $parent[] = trim(substr($a[1], 11));
					}
					else break;
				}
			}
			else $source[0][1] = '?>' . $source[0][1];

			if (is_array($a = $source[$len - 1]))
			{
				if (T_CLOSE_TAG == $a[0]) $a[1] = ';';
				else if (T_INLINE_HTML == $a[0]) $a[1] .= '<?php ';
			}

			array_walk($source, array(__CLASS__, 'echoToken'));
		}

		self::$configSource[$config] = ob_get_clean();


		// Parent's config file path is relative to the current application's directory

		$len = count($parent);
		for ($i = 0; $i < $len; ++$i)
		{
			$a =& $parent[$i];

			if ('__patchwork__' == substr($a, 0, 13)) $a = self::$rootPath . substr($a, 13);

			if ('/' !== $a[0] && '\\' !== $a[0] && ':' !== $a[1]) $a = $realpath . $a;

			if ('/*' === substr(strtr($a, '\\', '/'), -2) && $a = patchwork_realpath(substr($a, 0, -2)))
			{
				$a = rtrim($a, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
				$source = array();

				$p = array($a);
				unset($a);

				$pLen = 1;
				for ($j = 0; $j < $pLen; ++$j)
				{
					$d = $p[$j];

					if (file_exists($d . 'config.patchwork.php')) $source[] = $d;
					else if ($h = opendir($d))
					{
						while (false !== $file = readdir($h))
						{
							'.' !== $file[0] && is_dir($d . $file) && $p[$pLen++] = $d . $file . DIRECTORY_SEPARATOR;
						}
						closedir($h);
					}

					unset($p[$j]);
				}


				$p = array();

				foreach ($source as $source)
				{
					if (self::$rootPath != $source)
					{
						foreach (self::c3mro($source) as $a)
						{
							if (false !== $a = array_search($a, $p))
							{
								$p[$a] = $source;
								$source = false;
								break;
							}
						}

						$source && $p[] = $source;
					}
				}

				$a = count($p);

				array_splice($parent, $i, 1, $p);

				$i += --$a;
				$len += $a;
			}
			else
			{
				$source = patchwork_realpath($a);
				if (false === $source) die('Patchwork Error: Missing file ' . rtrim(strtr($a, '\\', '/'), '/') . '/config.patchwork.php');
				$source = rtrim($source, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

				$a = $source;
				if (self::$rootPath === $a) unset($parent[$i]);
			}
		}

		return $parent;
	}

	protected static function echoToken(&$token)
	{
		if (is_array($token))
		{
			if (in_array($token[0], array(T_COMMENT, T_WHITESPACE, T_DOC_COMMENT)))
			{
				$a = substr_count($token[1], "\n");
				$token[1] = $a ? str_repeat("\n", $a) : ' ';
			}

			echo $token[1];
		}
		else echo $token;
	}
}

<?php /*********************************************************************
 *
 *   Copyright : (C) 2006 Nicolas Grekas. All rights reserved.
 *   Email     : nicolas.grekas+patchwork@espci.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL, see COPYING
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


function patchwork_autoload($searched_class)
{
	global $patchwork_paths_token, $patchwork_autoload_cache;

	$last_patchwork_paths = count($GLOBALS['patchwork_paths']) - 1;

	if (false !== strpos($searched_class, ';') || false !== strpos($searched_class, "'")) return;

	$amark = $GLOBALS['a' . $patchwork_paths_token];
	$GLOBALS['a' . $patchwork_paths_token] = false;
	$bmark = $GLOBALS['b' . $patchwork_paths_token];

	$i = strrpos($searched_class, '__');
	$level = false !== $i ? substr($searched_class, $i+2) : false;

	if (false !== $level && '' !== $level && '' === ltrim(strtr($level, ' 0123456789', '#          ')))
	{
		// Namespace renammed class
		$class = substr($searched_class, 0, $i);
		$level = min($last_patchwork_paths, '00' === $level ? -1 : (int) $level);
	}
	else
	{
		$class = $searched_class;
		$level = $last_patchwork_paths;
	}

	$file = false;
	$lcClass = strtolower($class);

	if ($outerClass =& $GLOBALS['patchwork_autoload_prefix'] && $len = strlen($lcClass))
	{
		$i = 0;
		$cache = array();

		do
		{
			$c = ord($lcClass[$i]);
			if (isset($outerClass[$c]))
			{
				$outerClass =& $outerClass[$c];
				isset($outerClass[-1]) && $cache[] = $outerClass[-1];
			}
			else break;
		}
		while (++$i < $len);

		if ($cache) do
		{
			$file = array_pop($cache);
			$file = $i < $len || !is_string($file) || function_exists($file) ? call_user_func($file, $class) : $file;
		}
		while (!$file && $cache);
	}

	unset($outerClass);

	$outerClass = (bool) $file;
	$parent_class = $class . '__' . $level;
	$cache = false;
	$c = $searched_class == $class;

	if (!$file && ('_' == substr($class, -1) || !strncmp('_', $class, 1) || false !== strpos($class, '__')))
	{
		// Out of the path class: search for an existing parent

		if ($class == $searched_class) ++$level;

		do $parent_class = $class . '__' . (0<=--$level ? $level : '00');
		while ($level>=0 && !class_exists($parent_class, false));
	}
	else if (!$c || !class_exists($parent_class, false))
	{
		// Conventional class: search its parent in existing classes or on disk

		$file || $file = strtr($class, '_', '/') . '.php';

		if ($source = resolvePath('class/' . $file, $level, 0)) do
		{
			$file = $GLOBALS['patchwork_lastpath_level'];

			for (; $level >= $file; --$level)
			{
				$parent_class = $class . '__' . (0<=$level ? $level : '00');
				if (class_exists($parent_class, false)) break 2;
			}

			if ('patchwork_preprocessor' == $lcClass)
			{
				patchwork_include($source);
				break;
			}

			$cache = ((int)(bool)DEBUG) . (0>++$level ? -$level .'-' : $level);
			$cache = "./.class_{$class}.php.{$cache}.{$patchwork_paths_token}.zcache.php";

			if (!(file_exists($cache) && (PATCHWORK_TURBO || filemtime($cache) > filemtime($source))))
				call_user_func(array('patchwork_preprocessor', 'run'), $source, $cache, $level, $class);

			$current_pool = array();
			$parent_pool =& $GLOBALS['patchwork_autoload_pool'];
			$GLOBALS['patchwork_autoload_pool'] =& $current_pool;

			patchwork_include($cache);

			if (class_exists($searched_class, false)) $parent_class = false;
			if (false !== $parent_pool) $parent_pool[$parent_class ? $parent_class : $searched_class] = $cache;
		} while (0);
		else
		{
			for (; $level >= -1; --$level)
			{
				$parent_class = $class . '__' . (0<=$level ? $level : '00');
				if (class_exists($parent_class, false)) break;
			}
		}
	}

	$searched_class = strtolower($searched_class);

	if ($parent_class ? class_exists($parent_class) : (class_exists($searched_class, false) && !isset($patchwork_autoload_cache[$searched_class])))
	{
		if ($parent_class)
		{
			$parent_class = strtolower($parent_class);
			$class = "class {$searched_class} extends {$parent_class}{}\$GLOBALS['c{$patchwork_paths_token}']['{$searched_class}']=1;";
		}
		else
		{
			$parent_class = $searched_class;
			$class = '';
		}

		if (isset($GLOBALS['patchwork_abstract'][$parent_class])) $class && $class = 'abstract ' . $class;
		else if ($c)
		{
			method_exists($parent_class, '__static_construct') && $class .= "{$parent_class}::__static_construct();";

			if (method_exists($parent_class, '__static_destruct'))
			{
				$class = str_replace('{}', '{static $hunter' . $patchwork_paths_token . ';}', $class);
				$class .= "{$searched_class}::\$hunter{$patchwork_paths_token}=new hunter(array('{$parent_class}','__static_destruct'));";
			}
		}

		eval($class);
	}
	else $class = '';

	if (!PATCHWORK_TURBO) return;

	if ($class && isset($patchwork_autoload_cache[$parent_class]))
	{
		// Include class declaration in its closest parent

		$code = $patchwork_autoload_cache[$parent_class];
		$tmp = strrpos($code, '*');
		$file = substr($code, 0, $tmp);
		$code = substr($code, $tmp);
		$code = "\$GLOBALS['c{$patchwork_paths_token}']['{$parent_class}']=__FILE__.'{$code}';";

		$tmp = file_get_contents($file);
		if (false !== strpos($tmp, $code))
		{
			if (!$c)
			{
				$c = (string) mt_rand(1, mt_getrandmax());
				$patchwork_autoload_cache[$parent_class] = $file . '*' . $c;
				$c = substr($code, 0, strrpos($code, '*') + 1) . $c . "';";
				$class .= ';' . $c;
			}

			$tmp = str_replace($code, $class, $tmp);
			($cache == $file && $current_pool) || patchwork_atomic_write($tmp, $file, filemtime($file));
		}
	}
	else $tmp = false;

	if ($cache)
	{
		$GLOBALS['patchwork_autoload_pool'] =& $parent_pool;

		if ($current_pool)
		{
			// Add an include directive of parent's code in the derivated class

			$code = '<?php ?>';
			$tmp || $tmp = file_get_contents($cache);
			if ('<?php ' != substr($tmp, 0, 6)) $tmp = '<?php ?>' . $tmp;

			foreach ($current_pool as $class => &$c) $code = substr($code, 0, -2) . "class_exists('{$class}',0)||patchwork_include('{$c}');?>";

			$tmp = substr($code, 0, -2) . substr($tmp, 6);
			patchwork_atomic_write($tmp, $cache, filemtime($cache));
		}

		$cache = substr($cache, 9, -12-strlen($patchwork_paths_token));

		if ($amark)
		{
			// Marker substitution

			$code = $amark;
			$amark = $amark != $bmark;

			$tmp = strrpos($code, '*');
			$file = substr($code, 0, $tmp);
			$code = substr($code, $tmp);
			$code = "\$a{$patchwork_paths_token}=__FILE__.'{$code}'";

			$tmp = file_get_contents($file);
			if (false !== strpos($tmp, $code))
			{
				if ($amark)
				{
					$GLOBALS['a' . $patchwork_paths_token] = $bmark;
					$code = "isset(\$c{$patchwork_paths_token}['{$searched_class}'])||{$code}";
					$c = "isset(\$c{$patchwork_paths_token}['{$searched_class}'])||(patchwork_include('./.class_{$cache}.{$patchwork_paths_token}.zcache.php'))||1";
				}
				else
				{
					$code = "\$e{$patchwork_paths_token}=\$b{$patchwork_paths_token}={$code}";
					$bmark = (string) mt_rand(1, mt_getrandmax());
					$GLOBALS['a' . $patchwork_paths_token] = $GLOBALS['b' . $patchwork_paths_token] = $file . '*' . $bmark;
					$bmark = substr($code, 0, strrpos($code, '*') + 1) . $bmark . "'";
					$code = "({$code})&&\$d{$patchwork_paths_token}&&";
					$c = $outerClass ? "'{$cache}'" : ($level + $GLOBALS['patchwork_paths_offset']);
					$c = "\$c{$patchwork_paths_token}['{$searched_class}']={$c}";
					$c = "({$bmark})&&\$d{$patchwork_paths_token}&&({$c})&&";
				}

				$tmp = str_replace($code, $c, $tmp);
				patchwork_atomic_write($tmp, $file, filemtime($file));
			}
		}
		else if (!$bmark && file_exists('./.config.patchwork.php'))
		{
			// Global cache completion

			$amark = $outerClass ? "'{$cache}'" : ($level + $GLOBALS['patchwork_paths_offset']);
			$code = "\$c{$patchwork_paths_token}['{$searched_class}']={$amark};";

			$c = fopen('./.config.patchwork.php', 'ab');
			flock($c, LOCK_EX);
			fwrite($c, $code);
			fclose($c);
		}
	}
}

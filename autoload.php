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


function cia_autoload($searched_class)
{
	global $cia_paths_token, $cia_autoload_cache;

	$last_cia_paths = count($GLOBALS['cia_paths']) - 1;

	if (false !== strpos($searched_class, ';') || false !== strpos($searched_class, "'")) return;

	$amark = $GLOBALS['a' . $cia_paths_token];
	$GLOBALS['a' . $cia_paths_token] = false;
	$bmark = $GLOBALS['b' . $cia_paths_token];

	$i = strrpos($searched_class, '__');
	$level = false !== $i ? substr($searched_class, $i+2) : false;

	if (false !== $level && '' !== $level && '' === ltrim(strtr($level, ' 0123456789', '#          ')))
	{
		// Namespace renammed class
		$class = substr($searched_class, 0, $i);
		$level = min($last_cia_paths, '00' === $level ? -1 : (int) $level);
	}
	else
	{
		$class = $searched_class;
		$level = $last_cia_paths;
	}

	$file = false;
	$prefix = false;
	$lcClass = strtolower($class);

	foreach ($GLOBALS['cia_autoload_prefix'] as $c)
	{
		if ($c[0] == substr($lcClass, 0, strlen($c[0])))
		{
			$prefix = true;
			$file = call_user_func($c[1], $class);
			break;
		}
	}

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

		$i = $last_cia_paths - $level;
		if (0 > $i) $i = 0;

		$file || $file = strtr($class, '_', '/') . '.php';
		$file = 'class/' . $file;

		$paths =& $GLOBALS['cia_include_paths'];
		$nb_paths = count($paths);

		for (; $i < $nb_paths; ++$i)
		{
			$source = $paths[$i] .'/'. (0<=$level ? $file : substr($file, 6));

			if (file_exists($source))
			{
				$preproc = 'CIA_preprocessor';
				if ('cia_preprocessor' == $lcClass)
				{
					if ($level) $preproc .= '__0';
					else
					{
						require $source;
						break;
					}
				}

				$cache = ((int)(bool)DEBUG) . (0>$level ? -$level .'-' : $level);
				$cache = "./.class_{$class}.php.{$cache}.{$cia_paths_token}.zcache.php";

				file_exists($cache) || call_user_func(array($preproc, 'run'), $source, $cache, $level, $class);

				$current_pool = array();
				$parent_pool =& $GLOBALS['cia_autoload_pool'];
				$GLOBALS['cia_autoload_pool'] =& $current_pool;

				require $cache;

				if (class_exists($searched_class, false)) $parent_class = false;
				if (false !== $parent_pool) $parent_pool[$parent_class ? $parent_class : $searched_class] = $cache;

				break;
			}

			--$level;

			$parent_class = $class . '__' . (0<=$level ? $level : '00');

			if (class_exists($parent_class, false)) break;
		}
	}

	if ($parent_class && class_exists($parent_class, true))
	{
		$class = new ReflectionClass($parent_class);
		$class = ($class->isAbstract() ? 'abstract ' : '') . 'class ' . $searched_class . ' extends ' . $parent_class . '{}';

		if ($c)
		{
			method_exists($parent_class, '__static_construct') && $class .= "{$parent_class}::__static_construct();";
			method_exists($parent_class, '__static_destruct' ) && $class .= "register_shutdown_function(array('{$parent_class}','__static_destruct'));";
		}

		eval($class);
	}
	else $class = '';

	if (DEBUG) return;

	if ($class && isset($cia_autoload_cache->$parent_class))
	{
		// Include class declaration in its closest parent

		$code = $cia_autoload_cache->$parent_class;
		$tmp = strrpos($code, '-');
		$file = substr($code, 0, $tmp);
		$code = substr($code, $tmp);
		$code = "\$GLOBALS['c{$cia_paths_token}']->{$parent_class}=__FILE__.'{$code}';";

		$tmp = file_get_contents($file);
		if (false !== strpos($tmp, $code))
		{
			if (!$c)
			{
				$c = substr($code, 0, strrpos($code, '-') + 1) . mt_rand() . "';";
				$class .= ';' . $c;
			}

			$tmp = str_replace($code, $class, $tmp);
			($cache == $file && $current_pool) || cia_atomic_write($tmp, $file);
		}
	}
	else $tmp = false;

	if ($cache)
	{
		$GLOBALS['cia_autoload_pool'] =& $parent_pool;

		if ($current_pool)
		{
			// Add an include directive of parent's code in the derivated class

			$code = '<?php ?>';
			$tmp || $tmp = file_get_contents($cache);
			if ('<?php ' != substr($tmp, 0, 6)) $tmp = '<?php ?>' . $tmp;

			foreach ($current_pool as $class => &$c) $code = substr($code, 0, -2) . "class_exists('{$class}',0)||include '{$c}';?>";

			$tmp = substr($code, 0, -2) . substr($tmp, 6);
			cia_atomic_write($tmp, $cache);
		}

		$cache = substr($cache, 9, -12-strlen($cia_paths_token));

		if ($amark)
		{
			// Marker substitution

			$code = $amark;
			$amark = $amark != $bmark;

			$tmp = strrpos($code, '-');
			$file = substr($code, 0, $tmp);
			$code = substr($code, $tmp);
			$code = "\$a{$cia_paths_token}=__FILE__.'{$code}'";

			$tmp = file_get_contents($file);
			if (false !== strpos($tmp, $code))
			{
				if ($amark) $c = "class_exists('{$searched_class}',0)||(include './.class_{$cache}.{$cia_paths_token}.zcache.php')||1";
				else
				{
					$amark = $prefix ? "'{$cache}'" : ($level + $GLOBALS['cia_paths_offset']);
					$code = "\$b{$cia_paths_token}=" . $code;
					$c = substr($code, 0, strrpos($code, '-') + 1);
					$c = "(\$c{$cia_paths_token}->{$searched_class}={$amark})&&" . $c . mt_rand() . "'";
				}

				$tmp = str_replace($code, $c, $tmp);
				cia_atomic_write($tmp, $file);
			}
		}
		else if (!$bmark && file_exists('./.config.zcache.php'))
		{
			// Global cache completion

			$amark = $prefix ? "'{$cache}'" : ($level + $GLOBALS['cia_paths_offset']);
			$code = "\$c{$cia_paths_token}->{$searched_class}={$amark};";

			$c = fopen('./.config.zcache.php', 'ab');
			flock($c, LOCK_EX);
			fwrite($c, $code, strlen($code));
			fclose($c);
		}
	}
}

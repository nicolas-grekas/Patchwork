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


class patchwork_bootstrapper
{
	static

	$pwd,
	$cwd,
	$token = '',
	$zcache,
	$offset,
	$last,
	$appId = 0;


	protected static

	$file,
	$configCode = array(),
	$configSource = array(),
	$fSlice, $rSlice,
	$bootstrapper,
	$lock = null;


	static function getLock($caller)
	{
		$cwd =& self::$cwd;

		if ($caller)
		{
			$cwd = defined('PATCHWORK_BOOTPATH') && '' !== PATCHWORK_BOOTPATH ? PATCHWORK_BOOTPATH : '.';
			$cwd = rtrim($cwd, '/\\') . DIRECTORY_SEPARATOR;

			self::$bootstrapper = $cwd . '.patchwork.php';

			file_exists($cwd . 'config.patchwork.php')
				|| die("Patchwork Error: file {$cwd}config.patchwork.php not found. Did you set PATCHWORK_BOOTPATH correctly?");

			self::$pwd = dirname($caller) . DIRECTORY_SEPARATOR;
		}

		if (self::$lock = @fopen($cwd . '.patchwork.lock', 'xb'))
		{
			if (file_exists(self::$bootstrapper))
			{
				fclose(self::$lock);
				@unlink($cwd . '.patchwork.lock');
				if ($caller) die('Patchwork Error: file .patchwork.php exists in PATCHWORK_BOOTPATH. Please fix your public bootstrap file.');
				else return false;
			}

			flock(self::$lock, LOCK_EX);
			ob_start(array(__CLASS__, 'ob_handler'));

			@set_time_limit(0);

			// Load dependencies

			require self::$pwd . 'class/patchwork/bootstrapper/preprocessor.php';
			require self::$pwd . 'class/patchwork/bootstrapper/inheritance.php';
			require self::$pwd . 'class/patchwork/bootstrapper/updatedb.php';

			self::$file = self::$pwd . 'common.php';

			patchwork_bootstrapper_preprocessor__0::$file =& self::$file;
			patchwork_bootstrapper_preprocessor__0::ob_start($caller);

			return true;
		}
		else if ($h = fopen($cwd . '.patchwork.lock', 'rb'))
		{
			usleep(1000);
			flock($h, LOCK_SH);
			fclose($h);
			file_exists(self::$bootstrapper) || sleep(1);
		}

		if ($caller && !file_exists(self::$bootstrapper))
		{
			@unlink($cwd . '.patchwork.lock');
			return self::getLock(false);
		}
		else return false;
	}

	static function isReleased()
	{
		return !self::$lock;
	}

	static function ob_handler($buffer)
	{
		if ('' !== $buffer)
		{
			if (self::$lock)
			{
				fclose(self::$lock);
				self::$lock = null;
			}

			@unlink(self::$cwd . '.patchwork.lock');
		}

		return $buffer;
	}

	static function release()
	{
		ob_end_flush();

		if ('' === $buffer = ob_get_clean())
		{
			$cwd = self::$cwd;
			$T = self::$token;
			$a = array("<?php \$patchwork_autoload_cache = array(); \$c{$T} =& \$patchwork_autoload_cache; \$d{$T} = 1;");

			foreach (self::$configCode as &$code)
			{
				$a[] = "(\$e{$T}=\$b{$T}=\$a{$T}=__FILE__.'*" . mt_rand(1, mt_getrandmax()) . "')&&\$d{$T}&&0;";
				$a[] =& $code;
			}

			patchworkPath('class/patchwork.php', $level);
			$T = addslashes($cwd . ".class_patchwork.php.0{$level}.{$T}.zcache.php");
			$a[] = "
DEBUG || file_exists('{$T}') && include '{$T}';
class p extends patchwork {}
patchwork::start();";

			$a = implode('', $a);

			fwrite(self::$lock, $a, strlen($a));
			fclose(self::$lock);

			touch($cwd . '.patchwork.lock', $_SERVER['REQUEST_TIME'] + 1);

			if (IS_WINDOWS)
			{
				$a = new COM('Scripting.FileSystemObject');
				$a->GetFile($cwd . '.patchwork.lock')->Attributes |= 2; // Set hidden attribute
			}

			rename($cwd . '.patchwork.lock', self::$bootstrapper);

			self::$lock = self::$configCode = self::$configSource = self::$fSlice = self::$rSlice = null;

			@set_time_limit(ini_get('max_execution_time'));
		}
		else
		{
			die($buffer . "\n<br /><br />\n\n<small>---- Something has been echoed during bootstrap - dying ----</small>");
		}
	}

	static function initInheritance(&$patchwork_path)
	{
		self::$cwd = patchwork_realpath(rtrim(self::$cwd, '/\\')) . DIRECTORY_SEPARATOR;
		self::$bootstrapper = self::$cwd . basename(self::$bootstrapper);

		// Get include_path

		$patchwork_path = array();

		foreach (explode(PATH_SEPARATOR, get_include_path()) as $a)
		{
			$a = patchwork_realpath($a);
			if ($a && @opendir($a))
			{
				closedir();
				$patchwork_path[] = rtrim($a, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
			}
		}

		// Get linearized application inheritance graph

		$a = patchwork_bootstrapper_inheritance__0::getLinearizedGraph(self::$pwd, self::$cwd, self::$configSource, self::$appId);

		$patchwork_path = array_diff($patchwork_path, $a, array(''));
		$patchwork_path = array_merge($a, $patchwork_path);

		self::$last  = count($a) - 1;
		self::$offset = count($patchwork_path) - self::$last;
		self::$fSlice = array_slice($patchwork_path, 0, self::$last + 1);
		self::$rSlice = array_reverse(self::$fSlice);
	}

	static function initZcache()
	{
		// Get zcache's location

		$found = false;
		$patchwork_path =& $GLOBALS['patchwork_path'];

		for ($i = 0; $i <= self::$last; ++$i)
		{
			if (file_exists($patchwork_path[$i] . 'zcache/'))
			{
				$found = $patchwork_path[$i] . 'zcache' . DIRECTORY_SEPARATOR;

				if (@touch($i . '.patchwork.writeTest')) @unlink($i . '.patchwork.writeTest');
				else $found = false;

				break;
			}
		}

		if (!$found)
		{
			$found = $patchwork_path[0] . 'zcache' . DIRECTORY_SEPARATOR;
			file_exists($found) || mkdir($found);
		}

		self::$zcache = $found;
	}

	static function updatedb()
	{
		return patchwork_bootstrapper_updatedb__0::buildPathCache(
			$GLOBALS['patchwork_path'],
			self::$last,
			self::$cwd,
			self::$zcache
		);
	}

	static function preprocessorPass1()
	{
		return patchwork_bootstrapper_preprocessor__0::staticPass1();
	}

	static function preprocessorPass2()
	{
		$code = patchwork_bootstrapper_preprocessor__0::staticPass2();

		isset($GLOBALS['patchwork_autoload_cache']) || self::$token = md5(self::$token . $code);

		return self::$configCode[self::$file] = $code;
	}

	static function getBootstrapper()
	{
		return self::$bootstrapper;
	}

	protected static function loadConfig(&$slice, $name)
	{
		do
		{
			$file = each($slice);

			if (false === $file)
			{
				reset($slice);

				'preconfig' === $name && self::afterPreconfig();

				return false;
			}

			$file = $file[1] . $name . '.php';
		}
		while (!file_exists($file));

		self::$file = $file;

		return true;
	}

	static function loadConfigFile($type)
	{
		return self::loadConfig(self::$rSlice, $type . 'config');
	}

	static function loadConfigSource()
	{
		return self::loadConfig(self::$fSlice, 'config.patchwork');
	}

	static function getConfigSource()
	{
		return self::$configCode[self::$file] =& self::$configSource[self::$file];
	}

	static function afterPreconfig()
	{
		$T = self::$token = substr(self::$token, 0, 4);

		$a = self::$cwd . '.' . $T . '.zcache.php';
		if (!file_exists($a))
		{
			touch($a);

			if (IS_WINDOWS)
			{
				$b = new COM('Scripting.FileSystemObject');
				$b->GetFile($a)->Attributes |= 2; // Set hidden attribute
			}

			$b = opendir(self::$cwd);
			while (false !== $a = readdir($b))
			{
				if ('.zcache.php' == substr($a, -11) && '.' == $a[0]) @unlink(self::$cwd . $a);
			}
			closedir($b);
		}
		
		// Autoload markers

		$GLOBALS['patchwork_autoload_cache'] = array();
		$GLOBALS['c' . $T] =& $GLOBALS['patchwork_autoload_cache'];
		$GLOBALS['b' . $T] = $GLOBALS['a' . $T] = false;
	}
}

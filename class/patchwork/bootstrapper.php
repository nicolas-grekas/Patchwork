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
	const UTF8_BOM = "\xEF\xBB\xBF";

	static

	$pwd,
	$cwd,
	$token = '',
	$zcache,
	$offset,
	$last,
	$appId = 0,
	$buggyRealpath = false,

	$file,
	$configCode = array(),
	$configSource = array(),
	$fSlice, $rSlice;


	protected static $lock = null;


	static function getLock($bootstrapper, $retry = true)
	{
		$cwd = defined('PATCHWORK_BOOTPATH') && '' !== PATCHWORK_BOOTPATH ? PATCHWORK_BOOTPATH : '.';
		self::$cwd = self::realpath($cwd . '/config.patchwork.php');

		if (!self::$cwd)
		{
			'-' !== strtr(substr($cwd, -1), '-/\\', '#--') && $cwd .= DIRECTORY_SEPARATOR;
			die("Patchwork Error: file {$cwd}config.patchwork.php not found. Did you set PATCHWORK_BOOTPATH correctly?");
		}

		$cwd = self::$cwd = dirname(self::$cwd) . DIRECTORY_SEPARATOR;

		if (self::$lock = @fopen($cwd . '.patchwork.lock', 'xb'))
		{
			if (file_exists($cwd . '.patchwork.php'))
			{
				fclose(self::$lock);
				@unlink($cwd . '.patchwork.lock');
				if ($retry) die('Patchwork Error: file .patchwork.php exists in PATCHWORK_BOOTPATH. Please fix your public bootstrap file.');
				else return false;
			}

			flock(self::$lock, LOCK_EX);
			ob_start(array(__CLASS__, 'ob_handler'));

			self::$pwd = dirname($bootstrapper) . DIRECTORY_SEPARATOR;

			@set_time_limit(0);

			return true;
		}
		else if ($h = fopen($cwd . '.patchwork.lock', 'rb'))
		{
			usleep(1000);
			flock($h, LOCK_SH);
			fclose($h);
			file_exists($cwd . '.patchwork.php') || sleep(1);
		}

		if ($retry && !file_exists($cwd . '.patchwork.php'))
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

	static function releaseLock()
	{
		if (self::$lock)
		{
			fclose(self::$lock);
			self::$lock = null;
		}

		@unlink(self::$cwd . '.patchwork.lock');
	}

	static function ob_handler($buffer)
	{
		if ('' === $buffer)
		{
			++ob::$in_handler;

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

			if ('\\' == DIRECTORY_SEPARATOR)
			{
				$a = new COM('Scripting.FileSystemObject');
				$a->GetFile($cwd . '.patchwork.lock')->Attributes |= 2; // Set hidden attribute
			}

			rename($cwd . '.patchwork.lock', $cwd . '.patchwork.php');

			@set_time_limit(ini_get('max_execution_time'));

			--ob::$in_handler;
		}
		else self::releaseLock();

		self::$lock = self::$configCode = self::$configSource = null;

		return $buffer;
	}

	static function release()
	{
		$buffer = ob_get_clean();
		'' !== $buffer && die($buffer . "\n<br /><br />\n\n<small>---- Something has been echoed during bootstrap - dying ----</small>");
	}

	static function getPath()
	{
		// Get include_path

		$patchwork_path = array();

		foreach (explode(PATH_SEPARATOR, get_include_path()) as $a)
		{
			$a = self::realpath($a);
			if ($a && @opendir($a))
			{
				closedir();
				$patchwork_path[] = rtrim($a, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
			}
		}

		// Get linearized application inheritance graph

		$a = patchwork_bootstrapper_inheritance::getLinearizedGraph(self::$pwd, self::$cwd, self::$configSource, self::$appId);

		$patchwork_path = array_diff($patchwork_path, $a, array(''));
		$patchwork_path = array_merge($a, $patchwork_path);

		self::$last  = count($a) - 1;
		self::$offset = count($patchwork_path) - self::$last;
		self::$fSlice = array_slice($patchwork_path, 0, self::$last + 1);
		self::$rSlice = array_reverse(self::$fSlice);

		return $patchwork_path;
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
		return patchwork_bootstrapper_updatedb::buildPathCache(
			$GLOBALS['patchwork_path'],
			self::$last,
			self::$cwd,
			self::$zcache
		);
	}

	static function preprocessor_ob_start($caller_file)
	{
		patchwork_bootstrapper_preprocessor::$file =& self::$file;
		patchwork_bootstrapper_preprocessor::ob_start($caller_file);
	}

	static function preprocessorPass1()
	{
		return patchwork_bootstrapper_preprocessor::staticPass1();
	}

	static function preprocessorPass2($token = false)
	{
		$code = patchwork_bootstrapper_preprocessor::staticPass2();

		$token && self::$token = md5(self::$token . $code);

		return self::$configCode[self::$file] = $code;
	}

	static function afterPreconfig()
	{
		$T = self::$token = substr(self::$token, 0, 4);

		$a = self::$cwd . '.' . $T . '.zcache.php';
		if (!file_exists($a))
		{
			touch($a);

			if ('\\' == DIRECTORY_SEPARATOR)
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

	static function realpath($a)
	{
		static $s;

		if (!isset($s))
		{
			$s = function_exists('realpath') ? @realpath('.') : false;
			$s = $s && '.' !== $s;
		}

		if ($s) return realpath($a);

		$DS = DIRECTORY_SEPARATOR;

		do
		{
			if (isset($a[0]))
			{
				if ('\\' === $DS)
				{
					if ('/' === $a[0] || '\\' === $a[0])
					{
						$a = 'c:' . $a;
						break;
					}

					if (false !== strpos($a, ':')) break;
				}
				else if ('/' === $a[0]) break;
			}

			static $getcwd;

			if (!isset($getcwd))
			{
				if ($cwd = function_exists('getcwd') ? @getcwd() : '')
				{
					$getcwd = true;
				}
				else
				{
					$getcwd = function_exists('get_included_files') ? @get_included_files() : '';
					$getcwd = $getcwd ? $getcwd[0] : (!empty($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '.');

					$cwd = $getcwd = dirname($getcwd);
				}

				self::$buggyRealpath = $getcwd;
			}
			else if (true === $getcwd) $getcwd = @getcwd();
			else $cwd = $getcwd;

			$a = $cwd . $DS . $a;

			break;
		}
		while (0);

		if (isset($cwd) && '.' === $cwd) $prefix = '.';
		else if ('\\' === $DS)
		{
			$prefix = $a[0] . ':\\';
			$a = substr($a, 2);
		}
		else $prefix = '/';

		'\\' === $DS && $a = strtr($a, '/', '\\');

		$a = explode($DS, $a);
		$b = array();

		foreach ($a as $a)
		{
			if (!isset($a[0]) || '.' === $a) continue;
			if ('..' === $a) $b && array_pop($b);
			else $b[]= $a;
		}

		$a = $prefix . implode($DS, $b);

		'\\' === $DS && $a = strtolower($a);

		return file_exists($a) ? $a : false;
	}

	static function ini_get_bool($a)
	{
		switch ($b = strtolower(@ini_get($a)))
		{
		case 'on':
		case 'yes':
		case 'true':
			return 'assert.active' !== $a;

		case 'stdout':
		case 'stderr':
			return 'display_errors' === $a;

		default:
			return (bool) (int) $b;
		}
	}
}

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


class patchwork_bootstrapper_bootstrapper__0
{
	protected

	$marker,
	$cwd,
	$token,
	$dir,
	$preprocessor,
	$configCode = array(),
	$configSource = array(),
	$fSlice, $rSlice,
	$lock = null;


	function __construct(&$cwd, &$token)
	{
		$this->marker = md5(mt_rand(1, mt_getrandmax()));
		$this->cwd    =& $cwd;
		$this->token  =& $token;
		$this->dir    = dirname(__FILE__);

		function_exists('token_get_all')
			|| die('Patchwork error: Extension "tokenizer" is needed and not loaded');

		isset($_SERVER['REDIRECT_STATUS'])
			&& false !== strpos(php_sapi_name(), 'apache');
			&& '200' !== $_SERVER['REDIRECT_STATUS']
			&& die('Patchwork error: Initialization forbidden (try using the shortest possible URL)');

		file_exists($cwd . 'config.patchwork.php')
			|| die("Patchwork error: File config.patchwork.php not found in {$cwd}. Did you set PATCHWORK_BOOTPATH correctly?");

		if (headers_sent($file, $line) || ob_get_length())
		{
			if ($file)
			{
				$file = " in {$file} on line {$line}";
			}
			else
			{
				$file = array_slice(get_included_files(), 0, -3);
				$line = array_pop($file);
				$file = ", likely in " . ($file ? implode(', ', $file) . ' or ' : '') . $line;
			}

			die("Patchwork error: Something has been echoed before bootstrap{$file} (maybe some white space or a BOM?)");
		}
	}

	// Because $this->cwd is a reference, this has to be dynamic
	function getCompiledFile() {return $this->cwd . '.patchwork.php';}
	function getLockFile()     {return $this->cwd . '.patchwork.lock';}

	function getLock($caller, $retry = true)
	{
		$lock = $this->getLockFile();
		$file = $this->getCompiledFile();

		if ($this->lock = @fopen($lock, 'xb'))
		{
			if (file_exists($file))
			{
				fclose($this->lock);
				@unlink($lock);
				if ($retry)
				{
					$file = pathinfo($file);

					die("Patchwork error: File {$file['basename']} exists in {$file['dirname']}. Please fix your web bootstrap file.");
				}
				else return false;
			}

			flock($this->lock, LOCK_EX);

			$this->initialize($caller);

			return true;
		}
		else if ($h = $retry ? @fopen($lock, 'rb') : fopen($lock, 'rb'))
		{
			usleep(1000);
			flock($h, LOCK_SH);
			fclose($h);
			file_exists($file) || sleep(1);
		}
		else if ($retry)
		{
			$dir = dirname($lock);

			if (@touch($dir . '/.patchwork.writeTest')) @unlink($dir . '/.patchwork.writeTest');
			else
			{
				function_exists('realpath') && $dir = realpath($dir);

				$dir .= DIRECTORY_SEPARATOR;

				if ('.' === $dir[0] && function_exists('getcwd') && @getcwd())
				{
					$dir = getcwd() . DIRECTORY_SEPARATOR . $dir;
				}

				die("Patchwork error: Please change the permissions of the {$dir} directory so that the web server can write in it.");
			}
		}

		if ($retry && !file_exists($file))
		{
			@unlink($lock);
			return $this->getLock($caller, false);
		}
		else return false;
	}

	function isReleased()
	{
		return !$this->lock;
	}

	protected function initialize($caller)
	{
		ob_start(array($this, 'ob_handler'));

		@set_time_limit(0);

		$this->preprocessor = $a = $this->getPreprocessor();

		$a->file = dirname($caller) . '/common.php';
		$a->ob_start($caller);

		$GLOBALS['patchwork_preprocessor_alias'] = array();
	}

	function ob_handler($buffer)
	{
		if ('' !== $buffer)
		{
			if ($this->lock)
			{
				fclose($this->lock);
				$this->lock = null;
			}

			@unlink($this->getLockFile());
		}

		return $buffer;
	}

	function release()
	{
		ob_end_flush();

		if ('' === $buffer = ob_get_clean())
		{
			$T = $this->token;
			$a = array(
				"<?php \$patchwork_preprocessor_alias=array();",
				"\$patchwork_autoload_cache=array();",
				"\$c{$T}=&\$patchwork_autoload_cache;",
				"\$d{$T}=1;",
				"(\$e{$T}=\$b{$T}=\$a{$T}=__FILE__.'*" . mt_rand(1, mt_getrandmax()) . "')&&\$d{$T}&&0;",
			);

			foreach ($this->configCode as &$code)
			{
				if (false !== strpos($code, "/*{$this->marker}:"))
				{
					$code = preg_replace(
						"#/\\*{$this->marker}:(\d+)(.+?)\\*/#",
						"global \$a{$T},\$c{$T};isset(\$c{$T}['$2'])||\$a{$T}=__FILE__.'*$1';",
						str_replace("/*{$this->marker}:*/", '', $code)
					);
				}

				$a[] =& $code;
			}

			patchworkPath('class/patchwork.php', $level);
			$T = addslashes($this->cwd . ".class_patchwork.php.0{$level}.{$T}.zcache.php");
			$a[] = "
DEBUG || file_exists('{$T}') && include '{$T}';
class p extends patchwork {}
patchwork::start();
exit;"; // When php.ini's output_buffering is on, the buffer is sometimes not flushed...

			$a = implode('', $a);

			fwrite($this->lock, $a);
			fclose($this->lock);

			$T = $this->getLockFile();

			touch($T, $_SERVER['REQUEST_TIME'] + 1);

			if (IS_WINDOWS)
			{
				$a = new COM('Scripting.FileSystemObject');
				$a->GetFile($T)->Attributes |= 2; // Set hidden attribute
			}

			rename($T, $this->getCompiledFile());

			$this->lock = $this->configCode = $this->configSource = $this->fSlice = $this->rSlice = null;

			@set_time_limit(ini_get('max_execution_time'));
		}
		else
		{
			die($buffer . "\n<br /><br />\n\n<small>---- Something has been echoed during bootstrap - dying ----</small>");
		}
	}

	function preprocessorPass1()
	{
		return $this->preprocessor->staticPass1();
	}

	function preprocessorPass2()
	{
		$code = $this->preprocessor->staticPass2();

		isset($GLOBALS['patchwork_autoload_cache']) || $this->token = md5($this->token . $code);

		return $this->configCode[$this->preprocessor->file] = $code;
	}

	function getLinearizedInheritance($pwd)
	{
		$a = $this->getInheritance();
		$a->configSource =& $this->configSource;
		$a = $a->linearizeGraph($pwd, $this->cwd);

		$this->fSlice = array_slice($a[0], 0, $a[1] + 1);
		$this->rSlice = array_reverse($this->fSlice);

		return $a;
	}

	function getZcache(&$paths, $last)
	{
		// Get zcache's location

		$found = false;

		for ($i = 0; $i <= $last; ++$i)
		{
			if (file_exists($paths[$i] . 'zcache/'))
			{
				$found = $paths[$i] . 'zcache' . DIRECTORY_SEPARATOR;

				if (@touch($found . '.patchwork.writeTest')) @unlink($found . '.patchwork.writeTest');
				else $found = false;

				break;
			}
		}

		if (!$found)
		{
			$found = $paths[0] . 'zcache' . DIRECTORY_SEPARATOR;
			file_exists($found) || mkdir($found);
		}

		return $found;
	}


	function initConfig()
	{
		// Set $token and purge old code files

		$T = $this->token = substr($this->token, 0, 4);

		$a = $this->cwd . '.' . $T . '.zcache.php';
		if (!file_exists($a))
		{
			touch($a);

			if (IS_WINDOWS)
			{
				$h = new COM('Scripting.FileSystemObject');
				$h->GetFile($a)->Attributes |= 2; // Set hidden attribute
			}

			$h = opendir($this->cwd);
			while (false !== $a = readdir($h))
			{
				if ('.zcache.php' == substr($a, -11) && '.' == $a[0]) @unlink($this->cwd . $a);
			}
			closedir($h);
		}
		
		// Autoload markers

		$GLOBALS['patchwork_autoload_cache'] = array();
		$GLOBALS['c' . $T] =& $GLOBALS['patchwork_autoload_cache'];
		$GLOBALS['b' . $T] = $GLOBALS['a' . $T] = false;
	}

	function loadConfigFile($type)
	{
		return $this->loadConfig($this->rSlice, $type . 'config');
	}

	function loadConfigSource()
	{
		return $this->loadConfig($this->fSlice, 'config.patchwork');
	}

	function getConfigSource()
	{
		$file = $this->preprocessor->file;
		return $this->configCode[$file] =& $this->configSource[$file];
	}

	function updatedb($paths, $last, $zcache)
	{
		return $this->getUpdatedb()->buildPathCache($paths, $last, $this->cwd, $zcache);
	}

	function alias($function, $alias, $args, $return_ref = false)
	{
		$this->preprocessor->alias($function, $alias, $args, $return_ref, $this->marker);
	}


	protected function loadConfig(&$slice, $name)
	{
		do
		{
			$file = each($slice);

			if (false === $file)
			{
				reset($slice);
				return false;
			}

			$file = $file[1] . $name . '.php';
		}
		while (!file_exists($file));

		$this->preprocessor->file = $file;

		return true;
	}

	protected function getPreprocessor()
	{
		require $this->dir . '/preprocessor.php';

		return new patchwork_bootstrapper_preprocessor__0;
	}

	protected function getInheritance()
	{
		require $this->dir . '/inheritance.php';

		return new patchwork_bootstrapper_inheritance__0;
	}

	protected function getUpdatedb()
	{
		require $this->dir . '/updatedb.php';

		return new patchwork_bootstrapper_updatedb__0;
	}
}

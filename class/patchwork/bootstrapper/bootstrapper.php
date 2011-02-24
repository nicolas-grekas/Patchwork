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
			&& false !== strpos(php_sapi_name(), 'apache')
			&& '200' !== $_SERVER['REDIRECT_STATUS']
			&& die('Patchwork error: Initialization forbidden (try using the shortest possible URL)');

		file_exists($cwd . 'config.patchwork.php')
			|| die("Patchwork error: File config.patchwork.php not found in {$cwd}. Did you set PATCHWORK_BOOTPATH correctly?");

		if (headers_sent($file, $line) || ob_get_length())
		{
			die('Patchwork error: ' . $this->getEchoError($file, $line, ob_get_flush(), 'before bootstrap'));
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
					$file = $this->getBestPath($file);

					die("Patchwork error: File {$file} exists. Please fix your web bootstrap file.");
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
				$dir = $this->getBestPath($dir);

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

		$this->preprocessor = $this->getPreprocessor();
		$this->preprocessor->ob_start($caller);

		$caller = array(dirname($caller) . '/');
		$this->loadConfig($caller, 'common');

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
			$a = array(
				"<?php \$patchwork_preprocessor_alias=array();",
				"\$_patchwork_autoloaded=array();",
				"\$c\x9D=&\$_patchwork_autoloaded;",
				"\$d\x9D=1;",
				"(\$e\x9D=\$b\x9D=\$a\x9D=__FILE__.'*" . mt_rand(1, mt_getrandmax()) . "')&&\$d\x9D&&0;",
			);

			foreach ($this->configCode as &$code)
			{
				if (false !== strpos($code, "/*{$this->marker}:"))
				{
					$code = preg_replace(
						"#/\\*{$this->marker}:(\d+)(.+?)\\*/#",
						"global \$a\x9D,\$c\x9D;isset(\$c\x9D['$2'])||\$a\x9D=__FILE__.'*$1';",
						str_replace("/*{$this->marker}:*/", '', $code)
					);
				}

				$a[] =& $code;
			}

			patchworkPath('class/patchwork.php', $level);
			$b = addslashes("{$this->cwd}.class_patchwork.php.0{$level}.{$this->token}.zcache.php");
			$a[] = "DEBUG || file_exists('{$b}') && include '{$b}';";
			$a[] = "patchwork::start();";
			$a[] = "exit;"; // When php.ini's output_buffering is on, the buffer is sometimes not flushed...

			$a = implode('', $a);

			fwrite($this->lock, $a);
			fclose($this->lock);

			$b = $this->getLockFile();

			touch($b, $_SERVER['REQUEST_TIME'] + 1);

			if (IS_WINDOWS)
			{
				$a = new COM('Scripting.FileSystemObject');
				$a->GetFile($b)->Attributes |= 2; // Set hidden attribute
			}

			rename($b, $this->getCompiledFile());

			$this->lock = $this->configCode = $this->fSlice = $this->rSlice = null;

			@set_time_limit(ini_get('max_execution_time'));
		}
		else
		{
			echo $buffer;

			$buffer = $this->getEchoError($this->preprocessor->file, 0, $buffer, 'during bootstrap');

			die("\n<br><br>\n\n<small>&mdash; {$buffer}. Dying &mdash;</small>");
		}
	}

	function preprocessorPass1()
	{
		return $this->preprocessor->staticPass1();
	}

	function preprocessorPass2()
	{
		$code = $this->preprocessor->staticPass2();

		ob_get_length() && $this->release();

		isset($GLOBALS['_patchwork_autoloaded']) || $this->token = md5($this->token . $code);

		return $this->configCode[$this->preprocessor->file] = $code;
	}

	function getLinearizedInheritance($pwd)
	{
		$a = $this->getInheritance()->linearizeGraph($pwd, $this->cwd);

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
		$this->token = substr($this->token, 0, 4);

		if (!file_exists($a = "{$this->cwd}.{$this->token}.zcache.php"))
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

		$GLOBALS['_patchwork_autoloaded'] = array();
		$GLOBALS["c\x9D"] =& $GLOBALS['_patchwork_autoloaded'];
		$GLOBALS["b\x9D"] = $GLOBALS["a\x9D"] = false;
	}

	function loadConfigFile($type)
	{
		return true === $type
			? $this->loadConfig($this->fSlice, 'config.patchwork')
			: $this->loadConfig($this->rSlice, $type . 'config');
	}

	function updatedb($paths, $last, $zcache)
	{
		return $this->getUpdatedb()->buildPathCache($paths, $last, $this->cwd, $zcache);
	}

	function alias($function, $alias, $args, $return_ref = false)
	{
		return $this->preprocessor->alias($function, $alias, $args, $return_ref, $this->marker);
	}

	protected function getEchoError($file, $line, $what, $when)
	{
		if ($len = strlen($what))
		{
			if ('' === trim($what))
			{
				$what = $len > 1 ? $len . ' bytes of whitespace have' : 'One byte of whitespace has';
			}
			else if (0 === strncmp($what, "\xEF\xBB\xBF", 3))
			{
				$what = 'An UTF-8 byte order mark (BOM) has';
			}
			else
			{
				$what = $len > 1 ? $len . ' bytes have' : 'One byte has';
			}
		}
		else $what = 'Something has';

		if ($line)
		{
			$line = " in {$file} on line {$line} (maybe some whitespace or a BOM?)";
		}
		else if ($file)
		{
			$line = " in {$file}";
		}
		else
		{
			$line = array_slice(get_included_files(), 0, -3);
			$file = array_pop($line);
			$line = ' in ' . ($line ? implode(', ', $line) . ' or in ' : '') . $file;
		}

		return "{$what} been echoed {$when}{$line}";
	}

	protected function getBestPath($a)
	{
		// This function tries to work around very disabled hosts,
		// to get the best "realpath" for comprehensible error messages.

		function_exists('realpath') && $a = realpath($a);

		is_dir($a) && $a = trim($a, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		if ('.' === $a[0] && function_exists('getcwd') && @getcwd())
		{
			$a = getcwd() . DIRECTORY_SEPARATOR . $a;
		}

		return $a;
	}

	protected function loadConfig(&$slice, $name)
	{
		ob_flush();

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

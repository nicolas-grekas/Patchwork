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


// Mandatory PHP dependencies

function_exists('token_get_all') || die('Patchwork Error: Extension "tokenizer" is needed and not loaded');
preg_match('/^.$/u', '§')        || die('Patchwork Error: PCRE is not compiled with UTF-8 support');
isset($_SERVER['REDIRECT_STATUS']) && '200' !== $_SERVER['REDIRECT_STATUS'] && die('Patchwork Error: initialization forbidden (try using the shortest possible URL)');

if (extension_loaded('mbstring'))
{
	(ini_get('mbstring.func_overload') & MB_OVERLOAD_STRING)
		&& die('Patchwork Error: mbstring is overloading string functions');

	ini_get('mbstring.encoding_translation')
		&& !in_array(strtolower(ini_get('mbstring.http_input')), array('pass', 'utf-8'))
		&& die('Patchwork Error: mbstring is set to translate input encoding');
}


error_reporting(E_ALL | E_STRICT);

isset($_GET['p:']) && 'exit' === $_GET['p:'] && die('Exit requested');


// Acquire lock

if (!__patchwork_bootstrapper::getLock())
{
	require './.patchwork.php';
	return;
}


// Linearize applications inheritance graph

$a = __patchwork_bootstrapper::c3mro(__patchwork_bootstrapper::$pwd, __patchwork_bootstrapper::$cwd);
$a = array_slice($a, 1);
$a[] = __patchwork_bootstrapper::$pwd;


// Get include_path

$patchwork_path = array();

foreach (explode(PATH_SEPARATOR, get_include_path()) as $i)
{
	$i = @realpath($i);
	if ($i && $b = @opendir($i))
	{
		closedir($b);
		$patchwork_path[] = rtrim($i, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
	}
}

$patchwork_path = array_diff($patchwork_path, $a, array(''));
$patchwork_path = array_merge($a, $patchwork_path);

__patchwork_bootstrapper::$last   = count($a) - 1;
__patchwork_bootstrapper::$offset = count($patchwork_path) - __patchwork_bootstrapper::$last;


// Get zcache's location

$a = false;
for ($i = 0; $i <= __patchwork_bootstrapper::$last; ++$i)
{
	if (file_exists($patchwork_path[$i] . 'zcache/'))
	{
		$a = $patchwork_path[$i] . 'zcache' . DIRECTORY_SEPARATOR;

		if (@touch($a . 'write_test')) @unlink($a . 'write_test');
		else $a = false;

		break;
	}
}

if (!$a)
{
	$a = $patchwork_path[0] . 'zcache' . DIRECTORY_SEPARATOR;
	file_exists($a) || mkdir($a);
}

__patchwork_bootstrapper::$zcache = $a;


// Load preconfig

$a = __patchwork_bootstrapper::$last + 1;
$a = array_slice($patchwork_path, 0, $a);
$a = array_reverse($a);
foreach ($a as $a)
{
	$a .= 'preconfig.php';

	if (file_exists($a))
	{
		eval(__patchwork_bootstrapper::staticPass1($a));
		unset($a, $b);
		__patchwork_bootstrapper::staticPass2($a);
		__patchwork_bootstrapper::$token = md5(__patchwork_bootstrapper::$token . $a);
	}
}


__patchwork_bootstrapper::$token = substr(__patchwork_bootstrapper::$token, 0, 4);


// Purge sources cache

$a = __patchwork_bootstrapper::$cwd . '.' . __patchwork_bootstrapper::$token . '.zcache.php';
if (!file_exists($a))
{
	touch($a);

	if ('\\' == DIRECTORY_SEPARATOR)
	{
		$b = new COM('Scripting.FileSystemObject');
		$b->GetFile($a)->Attributes |= 2; // Set hidden attribute
	}

	$b = opendir(__patchwork_bootstrapper::$cwd);
	while (false !== $a = readdir($b))
	{
		if ('.zcache.php' == substr($a, -11) && '.' == $a[0]) @unlink(__patchwork_bootstrapper::$cwd . $a);
	}
	closedir($b);
}


// Autoload markers

$a = __patchwork_bootstrapper::$token;
$patchwork_autoload_cache = array();
${'c'.$a} =& $patchwork_autoload_cache;
${'b'.$a} = ${'a'.$a} = false;


// Load config

$a = __patchwork_bootstrapper::$last + 1;
$a = array_slice($patchwork_path, 0, $a);
$b =& __patchwork_bootstrapper::$configSource;
foreach ($a as $a)
{
	$a .= 'config.patchwork.php';
	isset($b[$a]) && __patchwork_bootstrapper::$configCode[$a] =& $b[$a];
}
unset($b);


// Load postconfig

$a = __patchwork_bootstrapper::$last + 1;
$a = array_slice($patchwork_path, 0, $a);
$a = array_reverse($a);
foreach ($a as $a)
{
	$a .= 'postconfig.php';

	if (file_exists($a))
	{
		eval(__patchwork_bootstrapper::staticPass1($a));
		unset($a, $b);
		__patchwork_bootstrapper::staticPass2();
	}
}


// Eval configs

foreach (__patchwork_bootstrapper::$configCode as __patchwork_bootstrapper::$file => $a)
{
	ob_start();
	eval($a);
	unset($a, $b);
	if ('' !== $a = ob_get_clean()) echo preg_replace('/' . __patchwork_bootstrapper::$selfRx . '\(\d+\) : eval\(\)\'d code/', __patchwork_bootstrapper::$file, $a);
}

unset($a);


// Setup hook

class p extends patchwork {}
patchwork_setup::hook();


// Save config and release lock

__patchwork_bootstrapper::release();


// Let's go

patchwork::start();
return;


class __patchwork_bootstrapper
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

	$selfRx,
	$file,
	$code,
	$configCode = array(),
	$configSource = array();


	protected static $lock;


	static function getLock($retry = true)
	{
		if (self::$lock = @fopen('./.patchwork.lock', 'xb'))
		{
			flock(self::$lock, LOCK_EX);
			ob_start(array(__CLASS__, 'ob_handler'));

			self::$selfRx = preg_quote(__FILE__, '/');
			self::$pwd = rtrim(dirname(__FILE__), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
			self::$cwd = function_exists('getcwd') ? @getcwd() : '';

			if (!self::$cwd)
			{
				ob_start();
				echo "Patchwork Error: Your system's getcwd() is bugged and workarounds failed."; // This will be erased if following workarounds succeed

				self::$cwd = realpath('.');

				if (self::$cwd && '.' !== self::$cwd) {}
				else if (file_put_contents('./.getcwd', '<?php return dirname(__FILE__);'))
				{
					self::$cwd = require './.getcwd';
					unlink('./.getcwd');
				}
				else self::$cwd = `pwd`;

				self::$cwd ? ob_end_clean() : exit();
			}

			self::$cwd = rtrim(self::$cwd, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

			set_time_limit(0);

			return true;
		}
		else if ($h = fopen('./.patchwork.lock', 'rb'))
		{
			usleep(1000);
			flock($h, LOCK_SH);
			fclose($h);
			file_exists('./.patchwork.php') || sleep(1);
		}

		if ($retry && !file_exists('./.patchwork.php'))
		{
			@unlink('./.patchwork.lock');
			return self::getLock(false);
		}
		else return false;
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
		$lock = self::$cwd . '.patchwork.lock';

		if ('' === $buffer)
		{
			++ob::$in_handler;

			$T = self::$token;
			$a = array("<?php \$patchwork_autoload_cache = array(); \$c{$T} =& \$patchwork_autoload_cache; \$d{$T} = 1;");

			foreach (self::$configCode as &$code)
			{
				$a[] = "(\$e{$T}=\$b{$T}=\$a{$T}=__FILE__.'*" . mt_rand(1, mt_getrandmax()) . "')&&\$d{$T}&&0;";
				$a[] =& $code;
			}

			patchworkPath('class/patchwork.php', $level);
			$T = "'./.class_patchwork.php.0{$level}.{$T}.zcache.php'";
			$a[] = "
DEBUG || file_exists({$T}) && include {$T};
class p extends patchwork {}
patchwork::start();";

			$a = implode('', $a);

			fwrite(self::$lock, $a, strlen($a));
			fclose(self::$lock);

			touch($lock, $_SERVER['REQUEST_TIME'] + 1);

			if ('\\' == DIRECTORY_SEPARATOR)
			{
				$a = new COM('Scripting.FileSystemObject');
				$a->GetFile($lock)->Attributes |= 2; // Set hidden attribute
			}

			rename($lock, './.patchwork.php');

			set_time_limit(ini_get('max_execution_time'));

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


	// C3 Method Resolution Order (like in Python 2.3) for multiple application inheritance
	// See http://python.org/2.3/mro.html

	static function c3mro($realpath, $firstParent = false)
	{
		static $cache = array();

		$resultSeq =& $cache[$realpath];

		// If result is cached, return it
		if (null !== $resultSeq) return $resultSeq;

		$parent = self::getParentApps($realpath);

		// If no parent app, result is trival
		if (!$parent && !$firstParent) return $resultSeq = array($realpath);

		if ($firstParent) array_unshift($parent, $firstParent);

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
				false !== $firstParent && $cache = array();
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

			if (!$parent) die('Patchwork Error: Inconsistent application hierarchy in ' . $realpath . 'config.patchwork.php');

			$resultSeq[] = $parent;

			foreach ($seqs as $k => &$seq)
			{
				if ($parent == current($seq)) unset($seqs[$k][key($seq)]);
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
		self::UTF8_BOM === substr($source, 0, 3) && $source = substr($source, 3);
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
			else $source[0][1] = '?'.'>' . $source[0][1];

			if (is_array($a = $source[$len - 1]))
			{
				if (T_CLOSE_TAG == $a[0]) $a[1] = ';';
				else if (T_INLINE_HTML == $a[0]) $a[1] .= '<'.'?php ';
			}

			array_walk($source, array(__CLASS__, 'echoToken'));
		}

		self::$configSource[$config] = ob_get_clean();


		// Parent's config file path is relative to the current application's directory

		$len = count($parent);
		for ($i = 0; $i < $len; ++$i)
		{
			$a =& $parent[$i];

			if ('__patchwork__' == substr($a, 0, 13)) $a = self::$pwd . substr($a, 13);

			if ('/' !== $a[0] && '\\' !== $a[0] && ':' !== $a[1]) $a = $realpath . $a;

			if ('/*' === substr(strtr($a, '\\', '/'), -2) && $a = realpath(substr($a, 0, -2)))
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
					if (self::$pwd != $source)
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
				$source = realpath($a);
				if (false === $source) die('Patchwork Error: Missing file ' . rtrim(strtr($a, '\\', '/'), '/') . '/config.patchwork.php');
				$source = rtrim($source, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

				$a = $source;
				if (self::$pwd === $a) unset($parent[$i]);
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


	static function buildPathCache()
	{
		global $patchwork_path;

		$parentPaths = array();

		$error_level = error_reporting(0);

		if (file_exists('./.parentPaths.txt'))
		{
			rename('./.parentPaths.txt', './.parentPaths.old');
			$old_db = fopen('./.parentPaths.old', 'rb');
		}
		else $old_db = false;

		$db = fopen('./.parentPaths.txt', 'wb');

		$path = array_flip($patchwork_path);
		unset($path[self::$cwd]);
		uksort($path, array(__CLASS__, 'dirCmp'));

		foreach ($path as $h => $level)
		{
			self::populatePathCache($old_db, $db, $parentPaths, $path, substr($h, 0, -1), $level);
		}

		fclose($db);
		$old_db && fclose($old_db) && unlink('./.parentPaths.old');

		if ('\\' == DIRECTORY_SEPARATOR)
		{
			$h = new COM('Scripting.FileSystemObject');
			$h->GetFile(self::$cwd . '.parentPaths.txt')->Attributes |= 2; // Set hidden attribute
		}

		error_reporting($error_level);

		$db = $h = false;

		if (function_exists('dba_handlers'))
		{
			$h = array('cdb','db2','db3','db4','qdbm','gdbm','ndbm','dbm','flatfile','inifile');
			$h = array_intersect($h, dba_handlers());
			$h || $h = dba_handlers();
			@unlink('./.parentPaths.db');
			if ($h) foreach ($h as $db) if ($h = @dba_open(self::$cwd . '.parentPaths.db', 'nd', $db, 0600)) break;
		}

		if ($h)
		{
			foreach ($parentPaths as $path => &$level)
			{
				sort($level);
				dba_insert($path, implode(',', $level), $h);
			}

			dba_close($h);

			if ('\\' == DIRECTORY_SEPARATOR)
			{
				$h = new COM('Scripting.FileSystemObject');
				$h->GetFile(self::$cwd . '.parentPaths.db')->Attributes |= 2; // Set hidden attribute
			}
		}
		else
		{
			$db = false;

			foreach ($parentPaths as $path => &$level)
			{
				$path = md5($path);
				$path = $path[0] . '/' . $path[1] . '/' . substr($path, 2) . '.path.txt';

				if (false === $h = @fopen(self::$zcache . $path, 'wb'))
				{
					@mkdir(self::$zcache . $path[0]);
					@mkdir(self::$zcache . substr($path, 0, 3));
					$h = fopen(self::$zcache . $path, 'wb');
				}

				sort($level);
				fwrite($h, implode(',', $level));
				fclose($h);
			}
		}

		return $db;
	}

	protected static function populatePathCache(&$old_db, &$db, &$parentPaths, &$path, $root, $level, $prefix = '', $subdir = '/')
	{
		// Kind of updatedb with mlocate strategy

		$dir = $root . ('\\' === DIRECTORY_SEPARATOR ? strtr($subdir, '/', '\\') : $subdir);

		static $old_db_line, $populated = array();

		if ('/' === $subdir)
		{
			if (isset($populated[$dir])) return;

			$populated[$dir] = true;

			if ($level > self::$last)
			{
				$prefix = '/class';
				$parentPaths['class'][] = $level;
			}
		}

		isset($old_db_line) || $old_db_line = $old_db ? fgets($old_db) : false;

		if (false !== $old_db_line)
		{
			do
			{
				$h = explode('*', $old_db_line, 2);
				false !== strpos($h[0], '%') && $h[0] = rawurldecode($h[0]);

				if (0 <= $h[0] = self::dirCmp($h[0], $dir))
				{
					if (0 === $h[0] && max(filemtime($dir), filectime($dir)) === (int) $h[1])
					{
						if ('/' !== $subdir && false !== strpos($h[1], '/0config.patchwork.php/'))
						{
							if (isset($path[$dir]))
							{
								$populated[$dir] = true;

								$root   = substr($dir, 0, -1);
								$subdir = '/';
								$level  = $path[$dir];

								if ($level > self::$last)
								{
									$prefix = '/class';
									$parentPaths['class'][] = $level;
								}
								else $prefix = '';
							}
							else break;
						}

						fwrite($db, $old_db_line);

						$h = explode('/', $h[1]);
						unset($h[0], $h[count($h)]);

						foreach ($h as $file)
						{
							$h = $file[0];

							$file = $subdir . substr($file, 1);
							$parentPaths[substr($prefix . $file, 1)][] = $level;

							$h && self::populatePathCache($old_db, $db, $parentPaths, $path, $root, $level, $prefix, $file . '/');
						}

						return;
					}

					break;
				}
			}
			while (false !== $old_db_line = fgets($old_db));
		}

		if ($h = opendir($dir))
		{
			static $now;
			isset($now) || $now = time() - 1;

			$files = array();
			$dirs  = array();

			while (false !== $file = readdir($h)) if ('.' !== $file[0] && 'zcache' !== $file)
			{
				if (is_dir($dir . $file)) $dirs[] = $file;
				else
				{
					$files[] = $file;

					if ('config.patchwork.php' === $file && '/' !== $subdir)
					{
						if (isset($path[$dir]))
						{
							$populated[$dir] = true;

							$root   = substr($dir, 0, -1);
							$subdir = '/';
							$level  = $path[$dir];

							if ($level > self::$last)
							{
								$prefix = '/class';
								$parentPaths['class'][] = $level;
							}
							else $prefix = '';
						}
						else
						{
							closedir($h);
							return;
						}
					}
				}
			}

			closedir($h);

			ob_start();

			echo strtr($dir, array('%' => '%25', "\r" => '%0D', "\n" => '%0A', '*' => '%2A')),
				'*', min($now, max(filemtime($dir), filectime($dir))), '/';

			foreach ($files as $file)
			{
				echo '0', $file, '/';
				$parentPaths[substr($prefix . $subdir . $file, 1)][] = $level;
			}

			if ($dirs)
			{
				'\\' === DIRECTORY_SEPARATOR || sort($dirs, SORT_STRING);

				echo '1', implode('/1', $dirs), '/';
			}

			echo "\n";

			fwrite($db, ob_get_clean());

			foreach ($dirs as $file)
			{
				$file = $subdir . $file;
				$parentPaths[substr($prefix . $file, 1)][] = $level;
				self::populatePathCache($old_db, $db, $parentPaths, $path, $root, $level, $prefix, $file . '/');
			}
		}
	}


	static function staticPass1($code)
	{
		self::$file = $code;
		$code = file_get_contents($code);
		self::UTF8_BOM === substr($code, 0, 3) && $code = substr($code, 3);
		false !== strpos($code, "\r") && $code = strtr(str_replace("\r\n", "\n", $code), "\r", "\n");
		$code = preg_replace('/\?'.'>$/', ';', $code);

		$mode = 2;
		$first_isolation = 0;
		$mode1_transition = false;

		$line = 1;
		$bracket = 0;

		$new_code = array();
		$transition = array();

		foreach (token_get_all($code) as $i => $token)
		{
			if (is_array($token))
			{
				$type = $token[0];
				$token = $token[1];
			}
			else $type = $token;

			switch ($type)
			{
			case T_OPEN_TAG:
				$token = '<?php ' . str_repeat("\n", substr_count($token, "\n"));
				break;

			case '(': ++$bracket; break;
			case ')': --$bracket; break;

			case T_DOC_COMMENT:
			case T_COMMENT:
				if ($mode1_transition && '/*#>*/' === $token)
				{
					$mode1_transition = false;
					if (1 !== $mode)
					{
						$transition[$i] = array($mode = 1, $line);
						$first_isolation = 0;
					}
				}
				else if (2 === $mode && '/*<*/' === $token) $transition[$i] = array($mode = 3, $line);
				else if (3 === $mode && '/*>*/' === $token) $transition[$i] = array($mode = 2, $line);

			case T_WHITESPACE:
				$token = substr_count($token, "\n");
				$token = $token ? str_repeat("\n", $token) : ' ';
				break;

			case T_CLOSE_TAG:
			case ';':
				if (1 < $mode && !$bracket && !$first_isolation) $first_isolation = 1;
				break;

			default:
				if (1 < $mode && 2 == $first_isolation)
				{
					$transition[$i] = array($mode = 2, $line);
					$first_isolation = 3;
				}
			}

			if (T_WHITESPACE === $type && false !== strpos($token, "\n"))
			{
				if (1 < $mode && 1 == $first_isolation) $first_isolation = 2;
				$mode1_transition = true;
			}
			else
			{
				$mode1_transition && 1 === $mode && $transition[$i] = array($mode = 2, $line);
				$mode1_transition = false;
			}

			$new_code[] = $token;
			$line += substr_count($token, "\n");
		}

		$code = '';
		ob_start();
		echo '__patchwork_bootstrapper::$code[1]=';

		$iLast = 0;
		$mode = 2;
		$line = '';

		foreach ($transition as $i => $transition)
		{
			$line = implode('', array_slice($new_code, $iLast, $i - $iLast));

			switch ($mode)
			{
			case 1: echo $line; break;
			case 2: echo var_export($line, true); break;
			case 3: echo $line, ')."', str_repeat('\n', substr_count($line, "\n")), '"'; break;
			}

			switch ($transition[0])
			{
			case 1: echo 2 === $mode ? ';' : ''; break;
			case 2: echo (3 !== $mode ? (2 === $mode ? ';' : ' ') . '__patchwork_bootstrapper::$code[' . $transition[1] . ']=' : '.'); break;
			case 3: echo '.__patchwork_bootstrapper::export('; break;
			}

			$mode = $transition[0];
			$iLast = $i;
		}

		$line = implode('', array_slice($new_code, $iLast));

		switch ($mode)
		{
		case 1: echo $line; break;
		case 2: echo var_export($line, true), ';'; break;
		case 3: echo $line, ')."', str_repeat('\n', substr_count($line, "\n")), '";'; break;
		}

		$code = ob_get_clean();
		ob_start();
		self::$code = array();

		return $code;
	}

	static function staticPass2(&$code = '')
	{
		if ('' !== $code = ob_get_clean()) echo preg_replace('/' . self::$selfRx . '\(\d+\) : eval\(\)\'d code/', self::$file, $code);

		$code = '?'.'>';
		$line = 1;
		foreach (self::$code as $i => $b)
		{
			$code .= str_repeat("\n", $i - $line) . $b;
			$line = $i + substr_count($b, "\n");
		}

		'?'.'><'.'?php' === substr($code, 0, 7) && $code = substr($code, 7);

		self::$code = array();
		self::$configCode[self::$file] = $code;
	}

	static function export($a)
	{
		if (is_array($a))
		{
			if ($a)
			{
				$b = array();
				foreach ($a as $k => &$a) $b[] = self::export($k, true) . '=>' . self::export($a);
				$b = 'array(' . implode(',', $b) . ')';
			}
			else return 'array()';
		}
		else if (is_object($a))
		{
			$b = array();
			$v = (array) $a;
			foreach ($v as $k => &$v)
			{
				if ("\0" === substr($k, 0, 1)) $k = substr($k, 3);
				$b[$k] =& $v;
			}

			$b = self::export($b);
			$b = get_class($a) . '::__set_state(' . $b . ')';
		}
		else if (is_string($a) && $a !== strtr($a, "\r\n\0", '---'))
		{
			$b = '"'. str_replace(
				array(  "\\",   '"',   '$',  "\r",  "\n",  "\0"),
				array('\\\\', '\\"', '\\$', '\\r', '\\n', '\\0'),
				$a
			) . '"';
		}
		else $b = var_export($a, true);

		return $b;
	}

	static function dirCmp($a, $b)
	{
		$len = min(strlen($a), strlen($b));

		if ('\\' === DIRECTORY_SEPARATOR)
		{
			$a = strtoupper(strtr($a, '\\', '/'));
			$b = strtoupper(strtr($b, '\\', '/'));
		}

		for ($i = 0; $i < $len; ++$i)
		{
			if ($a[$i] !== $b[$i])
			{
				if ('/' === $a[$i]) return -1;
				if ('/' === $b[$i]) return  1;
				return strcmp($a[$i], $b[$i]);
			}
		}

		return strlen($a) - strlen($b);
	}
}

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
isset($_SERVER['REDIRECT_URL'])  && die('Patchwork Error: $_SERVER[\'REDIRECT_URL\'] must not be set at this stage');
extension_loaded('mbstring')
	&& (ini_get('mbstring.func_overload') & MB_OVERLOAD_STRING)
	&& die('Patchwork Error: String functions are overloaded by mbstring');


error_reporting(E_ALL | E_STRICT);

isset($_GET['exit$']) && die('Exit requested');


// Acquire lock

if (!__patchwork_loader::getLock())
{
	require './.patchwork.php';
	return;
}


// Linearize applications inheritance graph

$a = __patchwork_loader::c3mro(__patchwork_loader::$pwd, __patchwork_loader::$cwd);
$a = array_slice($a, 1);
$a[] = __patchwork_loader::$pwd;


// Get include_path

$patchwork_path = explode(PATH_SEPARATOR, get_include_path());
$patchwork_path = array_map('realpath', $patchwork_path);
$patchwork_path = array_diff($patchwork_path, $a, array(''));
$patchwork_path = array_merge($a, $patchwork_path);

__patchwork_loader::$last   = count($a) - 1;
__patchwork_loader::$offset = count($patchwork_path) - __patchwork_loader::$last;


// Get zcache's location

$a = false;
for ($i = 0; $i <= __patchwork_loader::$last; ++$i)
{
	if (file_exists($patchwork_path[$i] . '/zcache/'))
	{
		$a = $patchwork_path[$i] . '/zcache/';

		if (@touch($a . 'write_test')) @unlink($a . 'write_test');
		else $a = false;

		break;
	}
}

if (!$a)
{
	$a = $patchwork_path[0] . '/zcache/';
	file_exists($a) || mkdir($a);
}

__patchwork_loader::$zcache = $a;


// Load preconfig

$a = __patchwork_loader::$last + 1;
$a = array_slice($patchwork_path, 0, $a);
$a = array_reverse($a);
foreach ($a as $a)
{
	$a .= DIRECTORY_SEPARATOR . 'preconfig.php';

	if (file_exists($a))
	{
		eval(__patchwork_loader::staticPass1($a));
		unset($a, $b);
		__patchwork_loader::staticPass2($a);
		__patchwork_loader::$token = md5(__patchwork_loader::$token . $a);
	}
}


__patchwork_loader::$token = substr(__patchwork_loader::$token, 0, 4);


// Purge sources cache

$a = __patchwork_loader::$cwd . '/.' . __patchwork_loader::$token . '.zcache.php';
if (!file_exists($a))
{
	touch($a);

	if ('\\' == DIRECTORY_SEPARATOR)
	{
		$b = new COM('Scripting.FileSystemObject');
		$b->GetFile($a)->Attributes |= 2; // Set hidden attribute
	}

	$b = opendir(__patchwork_loader::$cwd);
	while (false !== $a = readdir($b))
	{
		if ('.zcache.php' == substr($a, -11) && '.' == $a[0]) @unlink(__patchwork_loader::$cwd . '/' . $a);
	}
	closedir($b);
}


// Autoload markers

$a = __patchwork_loader::$token;
$patchwork_autoload_cache = array();
${'c'.$a} =& $patchwork_autoload_cache;
${'b'.$a} = ${'a'.$a} = false;


// Load config

$a = __patchwork_loader::$last + 1;
$a = array_slice($patchwork_path, 0, $a);
$b =& __patchwork_loader::$configSource;
foreach ($a as $a)
{
	$a .= DIRECTORY_SEPARATOR . 'config.patchwork.php';
	isset($b[$a]) && __patchwork_loader::$configCode[$a] =& $b[$a];
}
unset($b);


// Load postconfig

$a = __patchwork_loader::$last + 1;
$a = array_slice($patchwork_path, 0, $a);
$a = array_reverse($a);
foreach ($a as $a)
{
	$a .= DIRECTORY_SEPARATOR . 'postconfig.php';

	if (file_exists($a))
	{
		eval(__patchwork_loader::staticPass1($a));
		unset($a, $b);
		__patchwork_loader::staticPass2();
	}
}


// Eval configs

foreach (__patchwork_loader::$configCode as __patchwork_loader::$file => $a)
{
	ob_start();
	eval($a);
	unset($a, $b);
	if ('' !== $a = ob_get_clean()) echo preg_replace('/' . __patchwork_loader::$selfRx . '\(\d+\) : eval\(\)\'d code/', __patchwork_loader::$file, $a);
}

unset($a);


// Setup hook

class p extends patchwork {}
patchwork_setup::call();


// Save config and release lock

__patchwork_loader::release();


// Let's go

patchwork::start();
return;


class __patchwork_loader
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


	static function getLock()
	{
		self::$selfRx = preg_quote(__FILE__, '/');

		if (self::$lock = @fopen('./.patchwork.lock', 'xb'))
		{
			flock(self::$lock, LOCK_EX);
			ob_start(array(__CLASS__, 'ob_handler'));

			self::$pwd = dirname(__FILE__);
			self::$cwd = getcwd();

			set_time_limit(0);

			return true;
		}
		else
		{
			if ($h = fopen('./.patchwork.lock', 'rb'))
			{
				flock($h, LOCK_SH);
				fclose($h);
				file_exists('./.patchwork.php') || sleep(1);
			}

			return false;
		}
	}

	static function ob_handler($buffer)
	{
		$lock = self::$cwd . '/.patchwork.lock';

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

			resolvePath('class/patchwork.php');
			$T = "'./.class_patchwork.php.0{$GLOBALS['patchwork_lastpath_level']}.{$T}.zcache.php'";
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
		else
		{
			fclose(self::$lock);
			unlink($lock);
		}

		self::$lock = self::$configCode = self::$configSource = null;

		return $buffer;
	}

	static function release()
	{
		$buffer = ob_get_clean();
		'' !== $buffer && die($buffer . "\n<br /><br />\n\n<small>---- dying ----</small>");
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
		if (!$parent && !$firstParent) return array($realpath);

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

			if (!$parent) die('Patchwork Error: Inconsistent application hierarchy in ' . $realpath . DIRECTORY_SEPARATOR . 'config.patchwork.php');

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
		$config = $realpath . DIRECTORY_SEPARATOR . 'config.patchwork.php';


		// Get config's source and clean it

		file_exists($config)
			|| die('Patchwork Error: Missing file ' . $config);

		self::$appId += filemtime($config);

		$source = file_get_contents($config);
		self::UTF8_BOM === substr($source, 0, 3) && $source = substr($source, 3);
		false !== strpos($source, "\r") && $source = strtr(str_replace("\r\n", "\n", $source), "\r", "\n");

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

			array_walk($source, array(__CLASS__, 'flattenToken'));
		}

		self::$configSource[$config] = implode('', $source);


		// Parent's config file path is relative to the current application's directory

		$len = count($parent);
		for ($i = 0; $i < $len; ++$i)
		{
			$a =& $parent[$i];

			if ('__patchwork__' == substr($a, 0, 13)) $a = self::$pwd . substr($a, 13);

			if ('/' != $a[0] && '\\' != $a[0] &&  ':' != $a[1]) $a = $realpath . '/' . $a;

			if ('*' == substr($a, -1) && $a = realpath(substr($a, 0, -1)))
			{
				$source = array();

				$p = array($a);
				unset($a);

				$pLen = 1;
				for ($j = 0; $j < $pLen; ++$j)
				{
					$d = $p[$j];
					$a = file_exists($d . '/config.patchwork.php');
					$a && $source[] = $d;

					$h = opendir($d);
					while (false !== $file = readdir($h)) if ('.' !== $file && '..' !== $file)
					{
						if ($a && ('class' === $file || 'public' === $file || 'zcache' === $file)) continue;

						is_dir($d . '/' . $file) && $p[$pLen++] = $d . DIRECTORY_SEPARATOR . $file;
					}
					closedir($h);

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
				if (false === $source) die('Patchwork Error: Missing file ' . $a . DIRECTORY_SEPARATOR . 'config.patchwork.php');

				$a = $source;
				if (self::$pwd == $a) unset($parent[$i]);
			}
		}

		return $parent;
	}

	protected static function flattenToken(&$token)
	{
		is_array($token) && $token = $token[1];
	}


	static function buildPathCache($dba)
	{
		global $patchwork_path;

		$paths = array();

		foreach ($patchwork_path as $level => $h)
		{
			@self::populatePathCache($paths, $h, $level, $level <= self::$last ? '' : '/class');
		}


		if ($dba)
		{
			@unlink('./.parentPaths.db');
			$h = dba_open('./.parentPaths.db', 'n', $dba, 0600);
			foreach ($paths as $paths => $level) dba_insert($paths, substr($level, 0, -1), $h);
			dba_close($h);

			if ('\\' == DIRECTORY_SEPARATOR)
			{
				$h = new COM('Scripting.FileSystemObject');
				$h->GetFile(self::$cwd . '/.parentPaths.db')->Attributes |= 2; // Set hidden attribute
				unset($h);
			}
		}
		else
		{
			foreach ($paths as $paths => $level)
			{
				$paths = md5($paths);
				$paths = $paths[0] . '/' . $paths[1] . '/' . substr($paths, 2) . '.path.txt';

				if (false === $h = @fopen(self::$zcache . $paths, 'wb'))
				{
					@mkdir(self::$zcache . $paths[0]);
					@mkdir(self::$zcache . substr($paths, 0, 3));
					$h = fopen(self::$zcache . $paths, 'wb');
				}

				fwrite($h, substr($level, 0, -1));
				fclose($h);
			}
		}
	}

	protected static function populatePathCache(&$paths, $dir, $i, $prefix, $subdir = '/')
	{
		if ($h = opendir($dir . $subdir))
		{
			if ('/' != $subdir && file_exists($dir . $subdir . 'config.patchwork.php')) ;
			else while (false !== $file = readdir($h)) if ('.' != $file[0] && 'zcache' != $file)
			{
				$file = $subdir . $file;

				$paths[substr($prefix . $file, 1)] .= $i . ',';

				self::populatePathCache($paths, $dir, $i, $prefix, $file . '/');
			}

			closedir($h);
		}
	}


	static function staticPass1($a)
	{
		self::$file = $a;
		$a = file_get_contents($a);
		self::UTF8_BOM === substr($a, 0, 3) && $a = substr($a, 3);
		false !== strpos($a, "\r") && $a = strtr(str_replace("\r\n", "\n", $a), "\r", "\n");
		$a = preg_replace('/^<\?(?:php)?/', '', $a);
		$a = preg_replace('/\?>$/', ';', $a);


		$a = preg_split('#(^(?:\s*/\*\#>\*/.*)+)#m', $a, -1, PREG_SPLIT_DELIM_CAPTURE);

		$line = 0;
		$iLen = count($a);
		for ($i = 0; $i < $iLen; ++$i)
		{
			$b = array('');
			$c =& $b[0];
			$j = 0;

			foreach (token_get_all("<?php\n{$a[$i]}") as $token)
			{
				if (is_array($token))
				{
					$type = $token[0];
					$token = $token[1];
				}
				else $type = $token;

				switch ($type)
				{
				case T_COMMENT:
					if ('/*<*/' === $token)
					{
						$c = var_export($c, true);
						$c =& $b[];
						$c = '__patchwork_loader::export(';
						continue 2;
					}
					else if ('/*>*/' === $token)
					{
						$c .= ')."' . str_repeat('\n', substr_count($c, "\n")) . '"';
						$c =& $b[];
						$c = '';
						continue 2;
					}

				case T_DOC_COMMENT:
				case T_WHITESPACE:
					$token = substr_count($token, "\n");
					$token = $token ? str_repeat("\n", $token) : ' ';
					break;

				case T_CLOSE_TAG:
				case ';':
					$j || $j = -1;
					break;

				default:
					if (-1 === $j)
					{
						$j = count($b);
						$c = var_export($c, true);
						$c =& $b[];
						$c = '';
					}
				}

				$c .= $token;
			}

			$c = var_export($c, true);

			$b[0] = "'" . substr($b[0], 7);

			$a[$i] = ' __patchwork_loader::$code[' . $line . ']=';

			foreach ($b as $b => &$c)
			{
				if ($b === $j && $j > 0 && "''" !== $c)
				{
					$a[$i] .= '"";__patchwork_loader::$code[' . $line . ']=';
				}

				$a[$i] .= $c . '.';

				$line += substr_count($c, "\n");
			}

			$a[$i] .= '"";';

			if (++$i < $iLen) $line += substr_count($a[$i], "\n");
		}

		ob_start();
		self::$code = array();

		return implode('', $a);
	}

	static function staticPass2(&$a = '')
	{
		if ('' !== $a = ob_get_clean()) echo preg_replace('/' . self::$selfRx . '\(\d+\) : eval\(\)\'d code/', self::$file, $a);

		$a = '';
		$line = 0;
		foreach (self::$code as $i => $b)
		{
			$a .= str_repeat("\n", $i - $line) . $b;
			$line = $i + substr_count($b, "\n");
		}

		self::$code = array();
		self::$configCode[self::$file] = $a;
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
}

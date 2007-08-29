<?php



/**** Post-configuration stage 0 ****/



/**/$a = __patchwork_loader::$token;
define('PATCHWORK_PATH_TOKEN', /**/$a/**/);
${/**/'a'.$a/**/} = false;
${/**/'b'.$a/**/} = false;
${/**/'c'.$a/**/} = array();
$patchwork_autoload_cache =& ${/**/'c'.$a/**/};


isset($CONFIG['debug.allowed' ]) || $CONFIG['debug.allowed' ] = true;
isset($CONFIG['debug.password']) || $CONFIG['debug.password'] = '';

define('DEBUG', $CONFIG['debug.allowed'] && (!$CONFIG['debug.password'] || (isset($_COOKIE['debug_password']) && $CONFIG['debug.password'] == $_COOKIE['debug_password'])) ? 1 : 0);
define('TURBO', !DEBUG && isset($CONFIG['turbo']) && $CONFIG['turbo']);

unset($CONFIG['debug.allowed'], $CONFIG['debug.password'], $CONFIG['turbo']);


isset($CONFIG['umask']) && umask($CONFIG['umask']);


// file_exists replacement on Windows
// Fix a bug with long file names.
// In debug mode, checks if character case is strict.

/**/if ('\\' == DIRECTORY_SEPARATOR)
/**/{
		if (/**/PHP_VERSION < '5.2'/**/ || DEBUG)
		{
			if (DEBUG)
			{
				function win_file_exists($file)
				{
					if (file_exists($file) && $realfile = realpath($file))
					{
						$file = strtr($file, '/', '\\');

						$i = strlen($file);
						$j = strlen($realfile);

						while ($i-- && $j--)
						{
							if ($file[$i] != $realfile[$j])
							{
								if (strtolower($file[$i]) == strtolower($realfile[$j]) && !(0 == $i && ':' == substr($file, 1, 1))) trigger_error("Character case mismatch between requested file and its real path ({$file} vs {$realfile})");
								break;
							}
						}

						return true;
					}
					else return false;
				}
			}
			else
			{
				function win_file_exists($file) {return file_exists($file) && (strlen($file) < 100 || realpath($file));}
			}

			function win_is_file($file)       {return win_file_exists($file) && is_file($file);}
			function win_is_dir($file)        {return win_file_exists($file) && is_dir($file);}
			function win_is_link($file)       {return win_file_exists($file) && is_link($file);}
			function win_is_executable($file) {return win_file_exists($file) && is_executable($file);}
			function win_is_readable($file)   {return win_file_exists($file) && is_readable($file);}
			function win_is_writable($file)   {return win_file_exists($file) && is_writable($file);}
		}
/**/}


// __autoload(): the magic part

function __autoload($searched_class)
{
	$a = strtolower($searched_class);

	if ($a =& $GLOBALS['patchwork_autoload_cache'][$a])
	{
		if (is_int($a))
		{
			$b = $a;
			unset($a);
			$a = $b - /**/__patchwork_loader::$offset/**/;

			$b = $searched_class;
			$i = strrpos($b, '__');
			false !== $i && '__' === rtrim(strtr(substr($b, $i), ' 0123456789', '#          ')) && $b = substr($b, 0, $i);

			$a = $b . '.php.' . DEBUG . (0>$a ? -$a . '-' : $a);
		}

		$a = './.class_' . $a . /**/__patchwork_loader::$token . '.zcache.php'/**/;

		$GLOBALS[/**/'a' . __patchwork_loader::$token/**/] = false;

		if (file_exists($a))
		{
			patchwork_include($a);

			if (class_exists($searched_class, false)) return;
		}
	}

	static $load_autoload = true;

	if ($load_autoload)
	{
		require /**/__patchwork_loader::$pwd . '/autoload.php'/**/;
		$load_autoload = false;
	}


	patchwork_autoload($searched_class);
}


// patchworkProcessedPath(): added by the preprocessor in files in the include_path

function patchworkProcessedPath($file)
{
	$file = strtr($file, '\\', '/');
	$f = '.' . $file . '/';

	if (false !== strpos($f, './') || false !== strpos($file, ':'))
	{
		$f = realpath($file);
		if (!$f) return $file;

		$file = false;
		$i = /**/__patchwork_loader::$last + 1/**/;
		$p =& $GLOBALS['patchwork_path'];

		for (; $i < /**/count($patchwork_path)/**/; ++$i)
		{
			if (substr($f, 0, strlen($p[$i])+1) == $p[$i] . DIRECTORY_SEPARATOR)
			{
				$file = substr($f, strlen($p[$i])+1);
				break;
			}
		}

		if (false === $file) return $f;
	}

	$file = 'class/' . $file;

	$source = resolvePath($file);

	if (false === $source) return false;

	$level = $GLOBALS['patchwork_lastpath_level'];

	$file = strtr($file, '\\', '/');
	$cache = DEBUG . (0>$level ? -$level . '-' : $level);
	$cache = './.' . strtr(str_replace('_', '%2', str_replace('%', '%1', $file)), '/', '_')
		. '.' . $cache . /**/'.' . __patchwork_loader::$token . '.zcache.php'/**/;

	if (file_exists($cache) && (TURBO || filemtime($cache) > filemtime($source))) return $cache;

	patchwork_preprocessor::run($source, $cache, $level, false);

	return $cache;
}



/**** Post-configuration stage 1 ****/



function E($msg = '__getDeltaMicrotime') {return patchwork::log($msg, false, false);}


// Fix config

isset($CONFIG['i18n.lang_list'     ]) || $CONFIG['i18n.lang_list'] = '';
isset($CONFIG['maxage'        ]) || $CONFIG['maxage'] = 2678400;
isset($CONFIG['P3P'           ]) || $CONFIG['P3P'] = 'CUR ADM';
isset($CONFIG['session.save_path'    ]) || $CONFIG['session.save_path'] = PATCHWORK_ZCACHE;
isset($CONFIG['session.cookie_path'  ]) || $CONFIG['session.cookie_path'] = '/';
isset($CONFIG['session.cookie_domain']) || $CONFIG['session.cookie_domain'] = '';


define('PATCHWORK_I18N', false !== strpos($CONFIG['i18n.lang_list'], '|'));


/* patchwork's context initialization
*
* Setup needed environment variables if they don't exists :
*   $_SERVER['PATCHWORK_BASE']: application's base part of the url. Lang independant (ex. /myapp/__/)
*   $_SERVER['PATCHWORK_REQUEST']: request part of the url (ex. myagent/mysubagent/...)
*   $_SERVER['PATCHWORK_LANG']: lang (ex. en) if application is internationalized
*
* You can also define them with mod_rewrite, to get cleaner URLs for example.
*/

/**/if (isset($_SERVER['PATCHWORK_BASE']))
/**/{
/**/	if ($a = false !== strpos($_SERVER['PATCHWORK_BASE'], '/__/'))
			isset($_SERVER['PATCHWORK_BASE']) || $_SERVER['PATCHWORK_BASE'] = /**/$_SERVER['PATCHWORK_BASE']/**/;

/**/	if ('/'  == substr($_SERVER['PATCHWORK_BASE'], 0, 1))
			$_SERVER['PATCHWORK_BASE'] = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PATCHWORK_BASE'];

/**/	if (!isset($_SERVER['PATCHWORK_LANG']))
			isset($_SERVER['PATCHWORK_LANG']) || $_SERVER['PATCHWORK_LANG'] = '';

/**/	if ($a)
/**/	{
			if (isset($_SERVER['REDIRECT_URL']))
			{
				header('HTTP/1.1 200 OK');
				$_SERVER['PATCHWORK_LANG'] = '';
				$_SERVER['PATCHWORK_REQUEST'] = substr($_SERVER['REDIRECT_URL'], strlen(preg_replace("#^.*?://[^/]*#", '', $_SERVER['PATCHWORK_BASE'])) - 3);

				if (isset($_SERVER['REDIRECT_QUERY_STRING']))
				{
					$_SERVER['QUERY_STRING'] = $_SERVER['REDIRECT_QUERY_STRING'];
					parse_str($_SERVER['QUERY_STRING'], $_GET);
				}
			}
/**/	}
/**/}
/**/else
/**/{
/**/	if (!isset($_SERVER['PATH_INFO']))
/**/	{
/**/		// Check if the webserver supports PATH_INFO
/**/
/**/		$h = isset($_SERVER['HTTPS']) ? 'ssl' : 'tcp';
/**/		$h = fsockopen("{$h}://{$_SERVER['SERVER_ADDR']}", $_SERVER['SERVER_PORT'], $errno, $errstr, 30);
/**/		if (!$h) throw new Exception("Socket error n°{$errno}: {$errstr}");
/**/
/**/		$a = strpos($_SERVER['REQUEST_URI'], '?');
/**/		$a = false === $a ? $_SERVER['REQUEST_URI'] : substr($_SERVER['REQUEST_URI'], 0, $a);
/**/		'/' == substr($a, -1) && $a .= 'index.php';
/**/
/**/		$a  = "GET {$a}/_?exit$ HTTP/1.0\r\n";
/**/		$a .= "Host: {$_SERVER['HTTP_HOST']}\r\n";
/**/		$a .= "Connection: close\r\n\r\n";
/**/
/**/		fwrite($h, $a);
/**/		$a = fgets($h, 12);
/**/		fclose($h);
/**/
/**/		strpos($a, ' 4') || $_SERVER['PATH_INFO'] = '';
/**/
/**/		unset($a, $h);
/**/	}

		$_SERVER['PATCHWORK_BASE'] = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
		$_SERVER['PATCHWORK_LANG'] = $_SERVER['PATCHWORK_REQUEST'] = '';

/**/	if (isset($_SERVER['PATH_INFO']))
/**/	{
			isset($_SERVER['PATH_INFO']) && $_SERVER['PATCHWORK_REQUEST'] = substr($_SERVER['PATH_INFO'], 1);
			$_SERVER['PATCHWORK_BASE'] .= '/' . (PATCHWORK_I18N ? '__/' : '');
/**/	}
/**/	else
/**/	{
			'/index.php' == substr($_SERVER['PATCHWORK_BASE'], -10) && $_SERVER['PATCHWORK_BASE'] = substr($_SERVER['PATCHWORK_BASE'], 0, -10);
			$_SERVER['PATCHWORK_BASE'] .= '?' . (PATCHWORK_I18N ? '__/' : '');
			
			$_SERVER['PATCHWORK_REQUEST'] = $_SERVER['QUERY_STRING'];

			$a = strpos($_SERVER['QUERY_STRING'], '?');
			false !== $a || $a = strpos($_SERVER['QUERY_STRING'], '&');

			if (false !== $a)
			{
				$_SERVER['PATCHWORK_REQUEST'] = substr($_SERVER['QUERY_STRING'], 0, $a);
				$_SERVER['QUERY_STRING'] = substr($_SERVER['QUERY_STRING'], $a+1);
				parse_str($_SERVER['QUERY_STRING'], $_GET);
			}
			else if ('' !== $_SERVER['QUERY_STRING'])
			{
				$_SERVER['QUERY_STRING'] = '';

				$a = key($_GET);
				unset($_GET[$a], $_GET[$a]); // Double unset against a PHP security hole
			}

			$_SERVER['PATCHWORK_REQUEST'] = urldecode($_SERVER['PATCHWORK_REQUEST']);
/**/	}

		if (preg_match("#^(" . $CONFIG['i18n.lang_list'] . ")(?:/|$)(.*?)$#", $_SERVER['PATCHWORK_REQUEST'], $a))
		{
			$_SERVER['PATCHWORK_LANG']    = $a[1];
			$_SERVER['PATCHWORK_REQUEST'] = $a[2];
		}
/**/}


PATCHWORK_I18N || $_SERVER['PATCHWORK_LANG'] = $CONFIG['i18n.lang_list'];
define('PATCHWORK_DIRECT',  '_' == $_SERVER['PATCHWORK_REQUEST']);

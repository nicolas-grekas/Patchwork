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


// {{{ Shortcut for applications developers
if (PATCHWORK_I18N)
{
	function T($string, $lang = false)
	{
		if (!$lang) $lang = p::__LANG__();
		return TRANSLATOR::get($string, $lang, true);
	}
}
else
{
	function T($string) {return $string;}
}
// }}}

// {{{ PHP session mechanism overloading
class sessionHandler implements ArrayAccess
{
	function offsetGet($k)     {$_SESSION = SESSION::getAll(); return $_SESSION[$k];}
	function offsetSet($k, $v) {$_SESSION = SESSION::getAll(); $_SESSION[$k] =& $v;}
	function offsetExists($k)  {$_SESSION = SESSION::getAll(); return isset($_SESSION[$k]);}
	function offsetUnset($k)   {$_SESSION = SESSION::getAll(); unset($_SESSION[$k]);}

	static $id;

	static function close()   {return true;}
	static function gc($life) {return true;}

	static function open($path, $name)
	{
		session_cache_limiter('');
		ini_set('session.use_only_cookies', true);
		ini_set('session.use_cookies', false);
		ini_set('session.use_trans_sid', false);
		return true;
	}

	static function read($id)
	{
		$_SESSION = SESSION::getAll();
		self::$id = $id;
		return '';
	}

	static function write($id, $data)
	{
		if (self::$id != $id) SESSION::regenerateId();
		return true;
	}

	static function destroy($id)
	{
		SESSION::regenerateId(true);
		return true;
	}
}

session_set_save_handler(
	array($k = 'sessionHandler', 'open'),
	array($k, 'close'),
	array($k, 'read'),
	array($k, 'write'),
	array($k, 'destroy'),
	array($k, 'gc')
);

$_SESSION = new sessionHandler;
// }}}

// {{{ Database sugar
function DB($close = false)
{
	static $hunter, $db = false;

	if ($db || $close)
	{
		if ($close && $db) $db = adapter_DB::close($db) && false;
	}
	else
	{
		$hunter = new hunter('DB', array(true));
		$db = adapter_DB::connect();
	}

	return $db;
}
// }}}

function jsquote($a, $addDelim = true, $delim = "'")
{
	if ((string) $a === (string) ($a-0)) return $a-0;

	false !== strpos($a, "\r") && $a = strtr(str_replace("\r\n", "\n", $a), "\r", "\n");
	false !== strpos($a, '\\') && $a = str_replace('\\', '\\\\', $a);
	false !== strpos($a, "\n") && $a = str_replace("\n", '\n', $a);
	false !== strpos($a, '</') && $a = str_replace('</', '<\\/', $a);
	false !== strpos($a, $delim) && $a = str_replace($delim, '\\' . $delim, $a);

	if ($addDelim) $a = $delim . $a . $delim;

	return $a;
}

function jsquoteRef(&$a) {$a = jsquote($a);}

function patchwork_error_handler($code, $message, $file, $line, &$context)
{
	if (error_reporting())
	{
		switch ($code)
		{
		case E_NOTICE:
		case E_STRICT:
			if (strpos($message, '__00::')) return;

			static $offset = 0;
			$offset || $offset = -13 - strlen(PATCHWORK_PATH_TOKEN);

			if ('-' === substr($file, $offset, 1)) return;

			break;

		case E_WARNING:
			if (stripos($message, 'safe mode')) return;
		}

		class_exists('patchwork_error', false) || __autoload('patchwork_error'); // http://bugs.php.net/42098 workaround
		patchwork_error::handle($code, $message, $file, $line, $context);
	}
}

ini_set('log_errors', true);
ini_set('error_log', PATCHWORK_PROJECT_PATH . '/error.patchwork.log');
ini_set('display_errors', false);

set_error_handler('patchwork_error_handler');


class
{
	static

	$agentClass,
	$catchMeta = false;


	protected static

	$ETag = '',
	$LastModified = 0,

	$host,
	$lang = '',
	$base,
	$uri,

	$appId,
	$metaInfo,
	$metaPool = array(),
	$isGroupStage = true,
	$binaryMode = false,

	$maxage = false,
	$private = false,
	$expires = 'auto',
	$watchTable = array(),
	$headers = array(),

	$redirecting = false,
	$is_enabled = false,
	$ob_starting_level,
	$ob_level,
	$varyEncoding = false,
	$contentEncoding = false,
	$is304 = false,

	$agentClasses = '',
	$privateDetectionMode = false,
	$antiCSRFtoken,
	$detectCSRF = false,
	$total_time = 0,

	$allowGzip = array(
		'text/','script','xml','html','bmp','wav',
		'msword','rtf','excel','powerpoint',
	),

	$ieSniffedTypes = array(
		'text/plain','text/richtext','audio/x-aiff','audio/basic','audio/wav',
		'image/gif','image/jpeg','image/pjpeg','image/tiff','image/x-png','image/png',
		'image/x-xbitmap','image/bmp','image/x-jg','image/x-emf','image/x-wmf',
		'video/avi','video/mpeg','application/octet-stream','application/pdf',
		'application/base64','application/macbinhex40','application/postscript',
		'application/x-compressed','application/java','application/x-msdownload',
		'application/x-gzip-compressed','application/x-zip-compressed'
	),

	$ieSniffedTags = array(
		'body','head','html','img','plaintext',
		'a href','pre','script','table','title'
	);


	static function __constructStatic()
	{
#>		patchwork_debugger::call();

		if (!$CONFIG['clientside'])
		{
			unset($_COOKIE['JS'], $_COOKIE['JS']); // Double unset against a PHP security hole
		}
		else if (isset($_GET['$bin']))
		{
			preg_match('/[^.]+\.[^\.0-9]+$/', $_SERVER['HTTP_HOST'], $domain);
			$domain = isset($domain[0]) ? '.' . $domain[0] : false;
			self::setcookie('JS', isset($_COOKIE['JS']) && !$_COOKIE['JS'] ? '' : '0', 0, '/', $domain);
			header('Location: ' . preg_replace('/[\?&]\$bin[^&]*/', '', $_SERVER['REQUEST_URI']));
			exit;
		}

		self::$appId = abs($GLOBALS['patchwork_appId'] % 10000);

		// Language controler

		'' === $_SERVER['PATCHWORK_LANG']
			&& '' !== key($CONFIG['i18n.lang_list'])
			&& !PATCHWORK_DIRECT
			&& !isset($_GET['k$'])
			&& patchwork_language::negociate();

		self::setLang($_SERVER['PATCHWORK_LANG']);
		self::$uri = self::$host . substr($_SERVER['REQUEST_URI'], 1);
	}

	static function start()
	{
/*<
		self::log(
			'<a href="' . htmlspecialchars($_SERVER['REQUEST_URI']) . '" target="_blank">'
			. htmlspecialchars(preg_replace("'&v\\\$=[^&]*'", '', $_SERVER['REQUEST_URI']))
			. '</a>'
		);
		register_shutdown_function(array(__CLASS__, 'log'), '', true);
>*/

		// patchwork_appId cookie synchronisation

		if (!isset($_COOKIE['v$']) || $_COOKIE['v$'] != self::$appId)
		{
			$a = $CONFIG['i18n.lang_list'][$_SERVER['PATCHWORK_LANG']];
			$a = implode($a, explode('__', $_SERVER['PATCHWORK_BASE'], 2));
			$a = preg_replace("'\?.*$'", '', $a);
			$a = preg_replace("'^https?://[^/]*'i", '', $a);
			$a = dirname($a . ' ');
			if (1 === strlen($a)) $a = '';

			self::setcookie('v$', self::$appId, $_SERVER['REQUEST_TIME'] + $CONFIG['maxage'], $a .'/');
			$GLOBALS['patchwork_private'] = true;
		}


		// Setup output filters

		self::$is_enabled = true;
		self::$ob_starting_level = ob_get_level();
		ob_start(array(__CLASS__, 'ob_sendHeaders'));
		ob_start(array(__CLASS__, 'ob_filterOutput'), 8192);
		self::$ob_level = 2;


		// Anti Cross-Site-Request-Forgery / Javascript-Hijacking token

		if (
			isset($_COOKIE['T$'])
			&& (!IS_POSTING || (isset($_POST['T$']) && substr($_COOKIE['T$'], 1) === substr($_POST['T$'], 1)))
			&& '---------------------------------' === strtr($_COOKIE['T$'], '-ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', '#--------------------------------------------------------------')
		) self::$antiCSRFtoken = $_COOKIE['T$'];
		else self::getAntiCSRFtoken(true);

		isset($_GET['T$']) && $GLOBALS['patchwork_private'] = true;
		define('PATCHWORK_TOKEN_MATCH', isset($_GET['T$']) && substr(self::$antiCSRFtoken, 1) === substr($_GET['T$'], 1));
		if (IS_POSTING) unset($_POST['T$'], $_POST['T$']);


		// If the URL has an extension, check for static files

		$agent = $_SERVER['PATCHWORK_REQUEST'];
		if (($mime = strrchr($agent, '.')) && strcasecmp('.ptl', $mime)) patchwork_static::sendFile($agent, $mime);


		PATCHWORK_DIRECT ? self::clientside() : self::serverside();

		while (self::$ob_level)
		{
			ob_end_flush();
			--self::$ob_level;
		}
	}

	// {{{ Client side rendering controler
	static function clientside()
	{
		self::header('Content-Type: text/javascript');

		if (isset($_GET['v$']) && self::$appId != $_GET['v$'] && 'x$' !== key($_GET))
		{
			echo 'w(w.r(1,' . (int)!DEBUG . '))';
			return;
		}

		switch ( key($_GET) )
		{
		case 't$':
			patchwork_static::sendTemplate();
			break;

		case 'p$':
			patchwork_static::sendPipe();
			break;

		case 'a$':
			patchwork_clientside::render(array_shift($_GET), false);
			break;

		case 'x$':
			patchwork_clientside::render(array_shift($_GET), true);
			break;
		}
	}
	// }}}

	// {{{ Server side rendering controler
	static function serverside()
	{
		$agent = self::resolveAgentClass($_SERVER['PATCHWORK_REQUEST'], $_GET);

		if (isset($_GET['k$'])) return patchwork_agentTrace::send($agent);

		self::$binaryMode = 'text/html' !== substr(constant("$agent::contentType"), 0, 9);

		// Synch exoagents on browser request
		if (isset($_COOKIE['cache_reset_id']) && self::$appId == $_COOKIE['cache_reset_id'] && setcookie('cache_reset_id', '', 0, '/'))
		{
			self::touchAppId();
			self::touch('foreignTrace');
			self::touch('appId');

			self::setMaxage(0);
			self::setGroup('private');

			echo '<html><head><script type="text/javascript">location.',
				IS_POSTING ? 'replace(location)' : 'reload()',
				'</script></head></html>';

			return;
		}

/*<
		if (PATCHWORK_SYNC_CACHE && !self::$binaryMode)
		{
			global $patchwork_appId;

			$patchwork_appId = -$patchwork_appId - filemtime('./config.patchwork.php');
			self::touchAppId();
			$patchwork_appId = -$patchwork_appId - $_SERVER['REQUEST_TIME'];
			self::$appId = abs($patchwork_appId % 10000);

			$a = $CONFIG['i18n.lang_list'][$_SERVER['PATCHWORK_LANG']];
			$a = implode($a, explode('__', $_SERVER['PATCHWORK_BASE'], 2));
			$a = preg_replace("'\?.*$'", '', $a);
			$a = preg_replace("'^https?://[^/]*'i", '', $a);
			$a = dirname($a . ' ');
			if (1 === strlen($a)) $a = '';

			self::setcookie('v$', self::$appId, $_SERVER['REQUEST_TIME'] + $CONFIG['maxage'], $a .'/');

			self::touch('');

			for ($i = 0; $i < 16; ++$i) for ($j = 0; $j < 16; ++$j)
			{
				$dir = PATCHWORK_ZCACHE . dechex($i) . '/' . dechex($j) . '/';

				if (file_exists($dir))
				{
					$h = opendir($dir);
					while (false !== $file = readdir($h)) '.' !== $file && '..' !== $file && unlink($dir . $file);
					closedir($h);
				}
			}

			if (!IS_POSTING)
			{
				self::setMaxage(0);
				self::setGroup('private');

				echo '<html><head><script type="text/javascript">location.reload()</script></head></html>';
				return;
			}
		}
>*/

		// load agent
		if (IS_POSTING || self::$binaryMode || !isset($_COOKIE['JS']) || !$_COOKIE['JS'])
		{
			if (!self::$binaryMode) self::setGroup('private');
			patchwork_serverside::loadAgent($agent, false, false);
		}
		else patchwork_clientside::loadAgent($agent);
	}
	// }}}

	protected static function touchAppId()
	{
		// config.patchwork.php's last modification date is used for
		// version synchronisation with clients and caches.
		touch('./config.patchwork.php', $_SERVER['REQUEST_TIME']);
#>		file_exists('./.patchwork.php') && touch('./.patchwork.php', $_SERVER['REQUEST_TIME']);
	}

	static function disable($exit = false)
	{
		if (self::$is_enabled && ob_get_level() === self::$ob_starting_level + self::$ob_level)
		{
			while (self::$ob_level-- > 2) ob_end_clean();

			ob_end_clean();
			self::$is_enabled = false;
			ob_end_clean();
			self::$ob_level = 0;

			if (self::$is304) exit;
			if (!$exit) return true;
		}

		if ($exit) exit;

		return false;
	}

	static function setLang($lang)
	{
		if (!isset($CONFIG['i18n.lang_list'][$lang]) || isset(self::$lang) && self::$lang === $lang) return;

		self::$lang = $lang;

		$base = $CONFIG['i18n.lang_list'][$lang];
		$base = implode($base, explode('__', $_SERVER['PATCHWORK_BASE'], 2));

		self::$base = $base;

		self::$host = strtr($base, '#?', '//');
		self::$host = substr($base, 0, strpos(self::$host, '/', 8)+1);

		if (PATCHWORK_I18N && isset(self::$uri))
		{
			$base = preg_quote($_SERVER['PATCHWORK_BASE'], "'");
			$base = explode('__', $base, 2);
			$base[1] = '/' === $base[1] ? '[^?/]+/?' : ".+?{$base[1]}";
			$base = "'^{$base[0]}{$base[1]}(.*)$'D";

			preg_match($base, self::$uri, $base)
				? self::$uri = self::$base . self::translateRequest($base[1], $lang)
				: W('Something is wrong between p::$uri and PATCHWORK_BASE');
		}
	}

	static function __HOST__() {return self::$host;}
	static function __LANG__() {return self::$lang;}
	static function __BASE__() {return self::$base;}
	static function __URI__() {return self::$uri;}

	static function base($url = '', $noId = false)
	{
		$url = (string) $url;

		if (!preg_match("'^https?://'", $url))
		{
			$noId = '' === $url || $noId;

			if (strncmp('/', $url, 1)) $url = self::$base . $url;
			else $url = self::$host . substr($url, 1);

			if (!$noId && '/' !== substr($url, -1)) $url .= (false === strpos($url, '?') ? '?' : '&amp;') . self::$appId;
		}

		return $url;
	}

	static function translateRequest($req, $lang)
	{
		return $req;
	}

	static function getAntiCSRFtoken($new = false)
	{
		if ($new)
		{
			$new = isset($_COOKIE['T$']) && '1' === substr($_COOKIE['T$'], 0, 1) ? '1' : '2';

			if (!isset(self::$antiCSRFtoken) && $_COOKIE)
			{
				if (IS_POSTING)
				{
					$GLOBALS['_POST_BACKUP'] = $_POST;
					$_POST = array();

					$GLOBALS['_FILES_BACKUP'] = $_FILES;
					$_FILES = array();

					patchwork_antiCSRF::postAlert();
				}

				unset($_COOKIE['T$'], $_COOKIE['T$']); // Double unset against a PHP security hole
			}

			self::$antiCSRFtoken = $new . self::strongid();

			self::setcookie('T$', self::$antiCSRFtoken, 0, $CONFIG['session.cookie_path'], $CONFIG['session.cookie_domain']);
			$GLOBALS['patchwork_private'] = true;
		}

		return self::$antiCSRFtoken;
	}

	/*
	 * Replacement for PHP's header() function
	 */
	static function header($string, $replace = true, $http_response_code = null)
	{
		$string = preg_replace("'[\r\n].*'s", '', $string);
		$name = strtolower(substr($string, 0, strpos($string, ':')));

		if (self::$is_enabled)
		{
			if (   0 === stripos($string, 'http/')
				|| 0 === stripos($string, 'etag')
				|| 0 === stripos($string, 'expires')
				|| 0 === stripos($string, 'cache-control')
				|| 0 === stripos($string, 'content-length')
			) return;

			if (0 === stripos($string, 'last-modified'))
			{
				self::setLastModified(strtotime(trim(substr($string, 14))));
				return;
			}

			if (self::$catchMeta) self::$metaInfo[4][$name] = $string;

			if ('content-type' === $name && !self::$privateDetectionMode)
			{
				if (isset(self::$headers[$name])) return;

				if (false !== stripos($string, 'script'))
				{
					if (self::$private) PATCHWORK_TOKEN_MATCH || patchwork_antiCSRF::scriptAlert();

					self::$detectCSRF = true;
				}
			}
		}

		if (!self::$privateDetectionMode)
		{
			if ('text/' === substr($string, 14, 5) && !strpos($string, ';')) $string .= '; charset=UTF-8';

			self::$headers[$name] = $replace || !isset(self::$headers[$name]) ? $string : (self::$headers[$name] . ',' . substr($string, 1+strpos($string, ':')));
			header($string, $replace);
		}
	}

	static function setcookie($name, $value = '', $expires = null, $path = '', $domain = '', $secure = false, $httponly = false)
	{
		self::setrawcookie($name, urlencode($value), $expires, $path, $domain, $secure, $httponly);
	}

	static function setrawcookie($name, $value = '', $expires = 0, $path = '', $domain = '', $secure = false, $httponly = false)
	{
		isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 5') && $httponly = false;

		if ($value !== strtr($value, ",; \t\r\n\013\014", '--------')) setrawcookie($name, $value, $expires, $path, $domain, $secure);
		else
		{
			('' === (string) $value) && $expires = 1;

			if ($domain && '.' !== substr($domain, 0, 1)) W('setcookie() RFC incompatibility: $domain must start with a dot.');

			$GLOBALS['patchwork_private'] = true;
			header('P3P: CP="' . $CONFIG['P3P'] . '"');
			header(
				"Set-Cookie: {$name}={$value}" .
					($expires  ? '; expires=' . date('D, d-M-Y H:i:s T', $expires) : '') .
					($path     ? '; path='   . $path   : '') .
					($domain   ? '; domain=' . $domain : '') .
					($secure   ? '; secure'   : '') .
					($httponly ? '; HttpOnly' : ''),
				false
			);
		}
	}

	static function gzipAllowed($type)
	{
		$type = explode(';', $type);
		$type = strtolower($type[0]);
		$len  = strlen($type);

		foreach (self::$allowGzip as $p)
		{
			$p = strpos($type, $p);
			if (false !== $p && (0 === $p || $len - $p === strlen($p))) return true;
		}

		return false;
	}

	static function readfile($file, $mime)
	{
		return patchwork_static::readfile($file, $mime);
	}

	/*
	 * Redirect the web browser to an other GET request
	 */
	static function redirect($url = '')
	{
		if (self::$privateDetectionMode) throw new Exception;

		$url = (string) $url;
		$url = '' === $url ? '' : (preg_match("'^([^:/]+:/|\.+)?/'i", $url) ? $url : (self::$base . ('index' === $url ? '' : $url)));

		self::$redirecting = true;
		self::disable();

		if (PATCHWORK_DIRECT)
		{
			$url = 'location.replace(' . ('' !== $url
				? "'" . addslashes($url) . "'"
				: 'location')
			. ')';

			if (true === self::$contentEncoding) header('Content-Encoding: identity');
			header('Content-Length: ' . strlen($url));

			echo $url;
		}
		else
		{
			header('HTTP/1.1 302 Found');
			header('Location: ' . ('' !== $url ? $url : $_SERVER['REQUEST_URI']));
		}

		exit;
	}

	protected static function openMeta($agentClass, $is_trace = true)
	{
		self::$isGroupStage = true;

		self::$agentClass = $agentClass;
		if ($is_trace) self::$agentClasses .= '*' . self::$agentClass;

		$default = array(false, array(), false, array(), array(), false, self::$agentClass);

		self::$catchMeta = true;

		self::$metaPool[] =& $default;
		self::$metaInfo =& $default;
	}

	protected static function closeGroupStage()
	{
		self::$isGroupStage = false;

		return self::$metaInfo[1];
	}

	protected static function closeMeta()
	{
		self::$catchMeta = false;

		$poped = array_pop(self::$metaPool);

		$len = count(self::$metaPool);

		if ($len)
		{
			self::$metaInfo =& self::$metaPool[$len-1];
			self::$agentClass = self::$metaInfo[6];
		}
		else self::$agentClass = self::$metaInfo = null;

		return $poped;
	}

	static function setLastModified($LastModified)
	{
		if ($LastModified > self::$LastModified) self::$LastModified = $LastModified;
	}

	/*
	 * Controls cache max age
	 */
	static function setMaxage($maxage)
	{
		if ($maxage < 0) $maxage = $CONFIG['maxage'];
		else $maxage = min($CONFIG['maxage'], $maxage);

		if (!self::$privateDetectionMode)
		{
			if (false === self::$maxage) self::$maxage = $maxage;
			else self::$maxage = min(self::$maxage, $maxage);
		}

		if (self::$catchMeta)
		{
			if (false === self::$metaInfo[0]) self::$metaInfo[0] = $maxage;
			else self::$metaInfo[0] = min(self::$metaInfo[0], $maxage);
		}
	}

	/*
	 * Controls cache groups
	 */
	static function setGroup($group)
	{
		if ('public' === $group) return;

		$group = array_diff((array) $group, array('public'));

		if (!$group) return;

		if (self::$privateDetectionMode) throw new PrivateDetection;
		else if (self::$detectCSRF) PATCHWORK_TOKEN_MATCH || patchwork_antiCSRF::scriptAlert();

		self::$private = true;

		if (self::$catchMeta)
		{
			$a =& self::$metaInfo[1];

			if (1 === count($a) && 'private' === $a[0]) return;

			if (in_array('private', $group)) $a = array('private');
			else
			{
				$b = $a;

				$a = array_unique( array_merge($a, $group) );
				sort($a);

				if ($b != $a && !self::$isGroupStage)
				{
#>					W('Misconception: patchwork::setGroup() is called in ' . self::$agentClass . '->compose( ) rather than in ' . self::$agentClass . '->control(). Cache is now disabled for this agent.');

					$a = array('private');
				}
			}
		}
	}

	/*
	 * Controls cache expiration mechanism
	 */
	static function setExpires($expires)
	{
		if (!self::$privateDetectionMode) if ('auto' === self::$expires || 'ontouch' === self::$expires) self::$expires = $expires;

		if (self::$catchMeta) self::$metaInfo[2] = $expires;
	}

	static function watch($watch)
	{
		if (self::$catchMeta) self::$metaInfo[3] = array_merge(self::$metaInfo[3], (array) $watch);
	}

	static function canPost()
	{
		if (self::$catchMeta) self::$metaInfo[5] = true;
	}

	static function string($a)
	{
		return is_object($a) ? $a->__toString() : (string) $a;
	}

	static function mb_strtoupper_callback($m)
	{
		return mb_strtoupper($m[1]);
	}

	static function uniqid() {return md5(uniqid(mt_rand(), true));}

	static function strongid($length = 32)
	{
		static $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';

		$a = '';

		do
		{
			$i = 0;
			$n = unpack('C*', md5(uniqid(mt_rand(), true), true) . md5(uniqid(mt_rand(), true), true));

			do $a .= $chars[$n[++$i]%57] . $chars[$n[++$i]%57] . $chars[$n[++$i]%57] . $chars[$n[++$i]%57];
			while ($i < 32);

			$length -= 32;
		}
		while ($length > 0);

		$length && $a = substr($a, 0, $length);

		return $a;
	}


	protected static

	$saltLength = 4,
	$saltedHashTruncation = 32;

	static function saltedHash($pwd)
	{
		$salt = self::strongid(self::$saltLength);
		return substr($salt . md5($pwd . $salt), 0, self::$saltedHashTruncation);
	}

	static function matchSaltedHash($pwd, $saltedHash)
	{
		$salt = substr($saltedHash, 0, self::$saltLength);
		$pwd  = $salt . md5($pwd . $salt);

		return 0 === substr_compare($pwd, $saltedHash, 0, self::$saltedHashTruncation);
	}


	/*
	 * Clears files linked to $message
	 */
	static function touch($message)
	{
		if (is_array($message)) foreach ($message as &$message) self::touch($message);
		else
		{
			$message = preg_split("'[\\\\/]+'u", $message, -1, PREG_SPLIT_NO_EMPTY);
			$message = array_map('rawurlencode', $message);
			$message = implode('/', $message);
			$message = str_replace('.', '%2E', $message);

			$i = 0;

			$pool = array(self::getCachePath('watch/' . $message, 'txt'));

			$er = error_reporting(0);

			while ($message = array_pop($pool))
			{
				if (file_exists($message) && $h = fopen($message, 'rb'))
				{
					flock($h, LOCK_EX+LOCK_NB, $wb) || $wb = true;

					if (!$wb)
					{
						while ($line = fgets($h))
						{
							$a = $line[0];
							$line = substr($line, 1, -1);

							if ('I' === $a) $pool[] = $line;
							else if (file_exists($line)) unlink($line) && ++$i;
						}

						$wb = !IS_WINDOWS && unlink($message);
					}

					fclose($h);

					$wb || unlink($message);

					++$i;
				}
			}

			error_reporting($er);

#>			E("patchwork::touch('$message'): $i file(s) deleted.");
		}
	}

	/*
	 * Like mkdir(), but works with multiple level of inexistant directory
	 */
	static function makeDir($dir)
	{
		$dir = dirname($dir . ' ');

		if (file_exists($dir)) return;

		$dir = preg_split("'[/\\\\]+'u", $dir);

		if (!$dir) return;

		if ('' === $dir[0])
		{
			array_shift($dir);
			if (!$dir) return;
			$dir[0] = '/' . $dir[0];
		}
		else if (!(IS_WINDOWS && ':' === substr($dir[0], -1))) $dir[0] = './' . $dir[0];

		$new = '';

		foreach ($dir as $dir)
		{
			$new .= $dir . '/';
			file_exists($new) || mkdir($new);
		}
	}

	static function fopenX($file, &$readHandle = false)
	{
		if ($h = !file_exists($file))
		{
			self::makeDir($file);
			$h = @fopen($file, 'xb');
		}

		if ($h) flock($h, LOCK_EX);
		else if ($readHandle)
		{
			$readHandle = fopen($file, 'rb');
			flock($readHandle, LOCK_SH);
		}

		return $h;
	}

	/*
	 * Creates the full directory path to $filename, then writes $data to the file
	 */
	static function writeFile($filename, &$data, $Dmtime = 0)
	{
		$tmpname = dirname($filename) . '/' . uniqid(mt_rand(), true);

		$h = @fopen($tmpname, 'wb');

		if (!$h)
		{
			self::makeDir($tmpname);
			$h = @fopen($tmpname, 'wb');
		}

		if ($h)
		{
			fwrite($h, $data);
			fclose($h);

			if (IS_WINDOWS)
			{
				file_exists($filename) && unlink($filename);
				rename($tmpname, $filename);
			}
			else rename($tmpname, $filename);

			if ($Dmtime) touch($filename, $_SERVER['REQUEST_TIME'] + $Dmtime);

			return true;
		}
		else return false;
	}


	protected static function getCachePath($filename, $extension, $key = '')
	{
		if ('' !== (string) $extension) $extension = '.' . $extension;

		$hash = md5($filename . $extension . '.' . $key);
		$hash = $hash[0] . '/' . $hash[1] . '/' . substr($hash, 2);

		$filename = rawurlencode(str_replace('/', '.', $filename));
		$filename = substr($filename, 0, 224 - strlen($extension));

		return PATCHWORK_ZCACHE . $hash . '.' . $filename . $extension;
	}

	static function getContextualCachePath($filename, $extension, $key = '')
	{
		return self::getCachePath($filename, $extension, self::$base .'-'. self::$lang .'-'. DEBUG .'-'. PATCHWORK_PROJECT_PATH .'-'. $key);
	}

	static function log($message, $is_end = false, $raw_html = true)
	{
		static $prev_time = patchwork;
		self::$total_time += $a = 1000*(microtime(true) - $prev_time);

		if ('__getDeltaMicrotime' !== $message)
		{
			$mem = function_exists('memory_get_peak_usage') ? round(memory_get_peak_usage(true)/104857.6)/10 . 'M' : '';

			if (DEBUG && $is_end) $a = sprintf('<div>Total: %.01f ms%s</div></pre><pre>', self::$total_time, $mem ? ' - ' . $mem : '');
			else
			{
				$b = ob::$in_handler ? serialize($message) : print_r($message, true);

				if (!$raw_html) $b = htmlspecialchars($b);

				$a = '<acronym title="Date: ' . date("d-m-Y H:i:s", $_SERVER['REQUEST_TIME']) . ($mem ? ' - Memory: ' . $mem : '') . '">' . sprintf('%.01f ms', $a) . '</acronym> ' . $b . "\n";
			}

			$b = ini_get('error_log');
			$b = fopen($b ? $b : (PATCHWORK_PROJECT_PATH . '/error.patchwork.log'), 'ab');
			fwrite($b, $a);
			fclose($b);
		}

		$prev_time = microtime(true);

		return $a;
	}

	protected static function resolveAgentClass($agent, &$args)
	{
		static $resolvedCache = array();

		if (isset($resolvedCache['/' . $agent])) return 'agent_' . strtr($agent, '/', '_');

		if (preg_match("''u", $agent))
		{
			$ext = strrchr($agent, '.');

			if (false !== $ext && false === strpos($ext, '/'))
			{
				$agent = substr($agent, 0, -strlen($ext));
			}
			else $ext = '';

			$agent = '/' . $agent . '/';
			while (false !== strpos($agent, '//')) $agent = str_replace('//', '/', $agent);

			preg_match('"^/(?:[a-zA-Z0-9\x80-\xff]+(?:([-_ ])[a-zA-Z0-9\x80-\xff]+)*/)*"', $agent, $a);
		}
		else
		{
			$ext = '';
			$a = array('/');
		}

		$param = (string) substr($agent, strlen($a[0]), -1);
		$param = '' !== $param ? explode('/', $param) : array();
		$agent = (string) substr($a[0], 1, -1);

		if (isset($a[1])) $potentialAgent = (string) preg_replace_callback("'[-_ ](.)'u", array(__CLASS__, 'mb_strtoupper_callback'), $agent);
		else $potentialAgent = $agent;

		$lang = self::$lang;
		$l_ng = 2 < strlen($lang) ? substr($lang, 0, 2) : false;

		$agent = '/index';

		global $patchwork_lastpath_level;

		if ('' !== $potentialAgent)
		{
			$potentialAgent = explode('/', $potentialAgent);
			$potentialAgent_length = count($potentialAgent);

			$i = 0;
			$a = '';
			$offset = 0;

			while ($i < $potentialAgent_length)
			{
				$a .= '/' . $potentialAgent[$i++];

				if (isset($resolvedCache[$a]) || resolvePath("class/agent{$a}.php"))
				{
					$agent = $a;
					$agentLevel = isset($resolvedCache[$a]) ? true : $patchwork_lastpath_level;
					$offset = $i;
					continue;
				}

				if (
					   resolvePath("public/__{$a}.ptl")
					|| ($l_ng && resolvePath("public/{$l_ng}{$a}.ptl"))
					|| resolvePath("public/{$lang}{$a}.ptl")
				)
				{
					$agent = $a;
					$agentLevel = false;
					$offset = $i;
					continue;
				}

				if (
					   resolvePath("class/agent{$a}/")
					|| resolvePath("public/__{$a}/")
					|| ($l_ng && resolvePath("public/{$l_ng}{$a}/"))
					|| resolvePath("public/{$lang}{$a}/")
				) continue;

				break;
			}

			if ($offset < $potentialAgent_length) $param = array_merge(array_slice($potentialAgent, $offset), $param);
		}

		if ('' !== $ext)
		{
			$param
				? $param[count($param) - 1] .= $ext
				: $param = array($ext);
		}

		if ($param)
		{
			$args['__0__'] = implode('/', $param);

			$i = 0;
			foreach ($param as &$param) $args['__' . ++$i . '__'] = $param;
		}

		$resolvedCache[$agent] = true;

		$agent = 'agent' . strtr($agent, '/', '_');

		isset($agentLevel) || $agentLevel = resolvePath('class/agent/index.php') ? $patchwork_lastpath_level : false;

		if (true !== $agentLevel && !class_exists($agent, false))
		{
			if (false === $agentLevel) eval('class ' . $agent . ' extends agentTemplate {}');
			else $GLOBALS['patchwork_autoload_cache'][$agent] = $agentLevel + PATCHWORK_PATH_OFFSET;
		}

		return $agent;
	}

	protected static function agentArgs($agent)
	{
		$cache = self::getContextualCachePath('agentArgs/' . $agent, 'txt');
		$readHandle = true;
		if ($h = self::fopenX($cache, $readHandle))
		{
			// get declared arguments in $agent->get public property

			$args = get_class_vars($agent);
			$args =& $args['get'];

			is_array($args) || $args = (array) $args;
			$args && array_walk($args, array('self', 'stripArgs'));


			// autodetect private data for antiCSRF

			$private = '0';

			self::$privateDetectionMode = true;

			try
			{
				new $agent instanceof agent || W("Class {$agent} does not inherit from class agent");
			}
			catch (PrivateDetection $d)
			{
				$private = '1';
			}
			catch (Exception $d)
			{
			}


			// Cache results

			fwrite($h, $private . (DEBUG ? '' : serialize($args)));
			fclose($h);

			self::$privateDetectionMode = false;

			if ($private) $args[] = 'T$';
		}
		else
		{
			$cache = stream_get_contents($readHandle);
			fclose($readHandle);

#>			if (DEBUG)
#>			{
#>				$args = get_class_vars($agent);
#>				$args =& $args['get'];
#>
#>				is_array($args) || $args = (array) $args;
#>				$args && array_walk($args, array('self', 'stripArgs'));
#>			}
#>			else
#>			{
				$args = unserialize(substr($cache, 1));
#>			}

			if ($cache[0]) $args[] = 'T$';
		}

		return $args;
	}

	protected static function stripArgs(&$a, $k)
	{
		if (is_string($k)) $a = $k;

		$b = strpos($a, ':');
		if (false !== $b) $a = substr($a, 0, $b);
	}

	protected static function agentCache($agentClass, $keys, $type, $group = false)
	{
		if (false === $group) $group = self::$metaInfo[1];
		$keys = serialize(array($keys, $group));

		return self::getContextualCachePath($agentClass, $type, $keys);
	}

	static function writeWatchTable($message, $file = '', $exclusive = true)
	{
		$file && $file = realpath($file);
		if (!$file && !$exclusive) return;
		$file && $file = strtr($file, "\n\r", '--');

		foreach (array_unique((array) $message) as $message)
		{
			if ($file && self::$catchMeta) self::$metaInfo[3][] = $message;

			$message = preg_split("'[\\\\/]+'u", $message, -1, PREG_SPLIT_NO_EMPTY);
			$message = array_map('rawurlencode', $message);
			$message = implode('/', $message);
			$message = str_replace('.', '%2E', $message);

			$path = self::getCachePath('watch/' . $message, 'txt');
			if ($exclusive) self::$watchTable[$path] = (bool) $file;

			if (!$file) continue;

			if ($file_isnew = !file_exists($path)) self::makeDir($path);

			$h = fopen($path, 'ab');
			fwrite($h, 'U' . $file . "\n");
			fclose($h);

			if ($file_isnew)
			{
				$message = explode('/', $message);
				while (null !== array_pop($message))
				{
					$a = $path;
					$path = self::getCachePath('watch/' . implode('/', $message), 'txt');

					if ($file_isnew = !file_exists($path)) self::makeDir($path);

					$h = fopen($path, 'ab');
					fwrite($h, 'I' . $a . "\n");
					fclose($h);

					if (!$file_isnew) break;
				}
			}
		}
	}


	static function ob_filterOutput($buffer, $mode)
	{
		$one_chunk = $mode === (PHP_OUTPUT_HANDLER_START | PHP_OUTPUT_HANDLER_END);

		if ('' === $buffer && $one_chunk) return $buffer;


		static $type = false;
		false !== $type || $type = isset(self::$headers['content-type']) ? strtolower(substr(self::$headers['content-type'], 14)) : 'html';

		// Anti-XSRF token

		if (false !== strpos($type, 'html'))
		{
			static $lead;

			if (PHP_OUTPUT_HANDLER_START & $mode)
			{
				$lead = '';
#>				if ((!PATCHWORK_SYNC_CACHE || IS_POSTING) && !self::$binaryMode) $buffer = patchwork_debugger::sendProlog() . $buffer;
			}

			$tail = '';

			if (!(PHP_OUTPUT_HANDLER_END & $mode))
			{
				$a = strrpos($buffer, '<');
				if (false !== $a)
				{
					$tail = strrpos($buffer, '>');
					if (false !== $tail && $tail > $a) $a = $tail;

					$tail = substr($buffer, $a);
					$buffer = substr($buffer, 0, $a);
				}
			}

			$buffer = $lead . $buffer;
			$lead = $tail;


			$a = stripos($buffer, '<form');
			if (false !== $a)
			{
				$a = preg_replace_callback(
					'#<form\s(?:[^>]+?\s)?method\s*=\s*(["\']?)post\1.*?>#iu',
					array('patchwork_antiCSRF', 'appendToken'),
					$buffer
				);

				if ($a !== $buffer)
				{
					self::$private = true;
					if (!(isset($_COOKIE['JS']) && $_COOKIE['JS'])) self::$maxage = 0;
					$buffer = $a;
				}

				unset($a);
			}
		}
		else if (PHP_OUTPUT_HANDLER_START & $mode)
		{
			// Fix IE mime-sniff misfeature
			// (see http://www.splitbrain.org/blog/2007-02/12-internet_explorer_facilitates_cross_site_scripting
			//  and http://msdn.microsoft.com/library/default.asp?url=/workshop/networking/moniker/overview/appendix_a.asp)
			// This will break some binary contents, but it is very unlikely that a legitimate
			// binary content may contain the suspicious bytes that trigger IE mime-sniffing.

			$a = substr($buffer, 0, 256);
			$lt = strpos($a, '<');
			if (false !== $lt && (!$type || in_array($type, self::$ieSniffedTypes)))
			{
				foreach (self::$ieSniffedTags as $tag)
				{
					$tail = stripos($a, '<' . $tag, $lt);
					if (false !== $tail && $tail + strlen($tag) < strlen($a))
					{
						$buffer = substr($buffer, 0, $tail)
							. '<!--'
							. str_repeat(' ', max(1, 248 - strlen($tag) - $tail))
							. '-->'
							. substr($buffer, $tail);

						break;
					}
				}
			}
		}


		// GZip compression

		if (self::gzipAllowed($type))
		{
			if ($one_chunk)
			{
				if (strlen($buffer) > 100)
				{
					self::$varyEncoding = true;
					self::$is_enabled || header('Vary: Accept-Encoding', false);

					$mode = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';

					if ($mode)
					{
						$algo = array(
							'deflate'  => 'gzdeflate',
							'gzip'     => 'gzencode',
							'compress' => 'gzcompress',
						);

						foreach ($algo as $encoding => $algo) if (false !== stripos($mode, $encoding))
						{
							self::$contentEncoding = $encoding;
							self::$is_enabled || header('Content-Encoding: ' . $encoding);
							$buffer = $algo($buffer);
							break;
						}
					}
				}
			}
			else
			{
				self::$contentEncoding = true;
				self::$varyEncoding = true;
				if (!self::$is_enabled && (PHP_OUTPUT_HANDLER_START & $mode)) header('Vary: Accept-Encoding', false);
				$buffer = ob_gzhandler($buffer, $mode);
			}
		}

		if ($one_chunk && !self::$is_enabled) header('Content-Length: ' . strlen($buffer));

		return $buffer;
	}

	static function ob_sendHeaders($buffer)
	{
		if (self::$redirecting)
		{
			$buffer = '';
			return $buffer;
		}

		p::header(
			isset(self::$headers['content-type'])
				? self::$headers['content-type']
				: 'Content-Type: text/html'
		);

		$is304 = false;

		if (!IS_POSTING && ('' !== $buffer || self::$ETag))
		{
			if (!self::$maxage) self::$maxage = 0;
			if ($GLOBALS['patchwork_private']) self::$private = true;

			$LastModified = $_SERVER['REQUEST_TIME'];


			/* Write watch table */

			if ('ontouch' === self::$expires) self::$expires = 'auto';

			if ('auto' === self::$expires && self::$watchTable && !DEBUG)
			{
				self::$watchTable = array_keys(self::$watchTable);
				sort(self::$watchTable);

				$validator = $_SERVER['PATCHWORK_BASE'] .'-'. $_SERVER['PATCHWORK_LANG'] .'-'. PATCHWORK_PROJECT_PATH .'-'. DEBUG;
				$validator = substr(md5(serialize(self::$watchTable) . $validator), 0, 8);

				$ETag = $validator;

				$validator = PATCHWORK_ZCACHE . $validator[0] .'/'. $validator[1] .'/'. substr($validator, 2) .'.v.txt';

				$readHandle = true;
				if ($h = self::fopenX($validator, $readHandle))
				{
					$a = substr(md5(microtime(1)), 0, 8);
					fwrite($h, $a .'-'. $LastModified);
					fclose($h);

					$readHandle = "++\$i;unlink('$validator');\n";

					foreach (self::$watchTable as $path)
					{
						$h = fopen($path, 'ab');
						fwrite($h, $readHandle);
						fclose($h);
					}

					self::writeWatchTable('appId', $validator);
				}
				else
				{
					$a = fread($readHandle, 32);
					fclose($readHandle);

					$a = explode('-', $a);
					$LastModified = $a[1];
					$a = $a[0];
				}

				$ETag .= $a . (int)(bool) self::$private . sprintf('%08x', self::$maxage);
			}
			else
			{
				/* ETag / Last-Modified validation */

				$ETag = substr(
					md5(
						self::$ETag .'-'. $buffer .'-'. self::$expires .'-'. self::$maxage .'-'.
						(int)(bool)self::$private .'-'. implode('-', self::$headers)
					), 0, 8
				);

				if (self::$LastModified) $LastModified = self::$LastModified;
				else if (
					isset($_SERVER['HTTP_USER_AGENT'])
					&& strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')
					&& preg_match('/MSIE [0-6]\./', $_SERVER['HTTP_USER_AGENT'])
					&& self::gzipAllowed(strtolower(substr(self::$headers['content-type'], 14))))
				{
					// Patch an IE<=6 bug when using ETag + compression

					self::$private = true;

					$ETag = hexdec($ETag);
					if ($ETag > PHP_INT_MAX) $ETag -= PHP_INT_MAX + 1;
					$LastModified = $ETag;
					$ETag = dechex($ETag);
				}
			}

			$ETag = '"' . $ETag . '"';
			self::$ETag = $ETag;
			self::$LastModified = $LastModified;

			$is304 = (isset($_SERVER['HTTP_IF_NONE_MATCH'    ]) && $_SERVER['HTTP_IF_NONE_MATCH'] === $ETag)
			      || (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] === $LastModified);

			header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + (self::$private || !self::$maxage ? 0 : self::$maxage)));
			header('Cache-Control: max-age=' . self::$maxage . (self::$private ? ',private,must' : ',public,proxy') . '-revalidate');

			if ($is304)
			{
				$buffer = '';
				header('HTTP/1.1 304 Not Modified');
			}
			else
			{
				'' !== $buffer && header('Accept-Ranges: bytes');

				header('ETag: ' . $ETag);
				header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', $LastModified));
				self::$varyEncoding && header('Vary: Accept-Encoding', false);

				if ('' !== $buffer && ($range = isset($_SERVER['HTTP_RANGE'])
					? patchwork_httpRange::negociate(strlen($buffer), $ETag, $LastModified)
					: false))
				{
					self::$is_enabled = false;
					patchwork_httpRange::sendChunks($range, $buffer, self::$headers['content-type'], 0);
				}
			}
		}

		if (!$is304)
		{
			$h = self::$headers['content-type'];
			false !== stripos($h, 'html')     && header('P3P: CP="' . $CONFIG['P3P'] . '"');
			is_string(self::$contentEncoding) && header('Content-Encoding: ' . self::$contentEncoding);
			self::$is_enabled                 && header('Content-Length: ' . strlen($buffer));
		}

		self::$is304 = $is304;

		if ('HEAD' === $_SERVER['REQUEST_METHOD']) $buffer = '';

		return $buffer;
	}

	static function resolvePublicPath($filename, &$path_idx = 0)
	{
		if ($path_idx && $path_idx > PATCHWORK_PATH_LEVEL) return false;

		static $last_lang,
			$last__in_filename = '', $last__in_path_idx,
			$last_out_filename,      $last_out_path_idx;

		$lang = self::__LANG__() . '/';
		$l_ng = 2 < strlen($lang) ? substr($lang, 0, 2) . '/' : false;


		if ($filename == $last__in_filename && $lang == $last_lang && $last__in_path_idx <= $path_idx && $path_idx <= $last_out_path_idx)
		{
			$path_idx = $last_out_path_idx;
			return $last_out_filename;
		}

		$last_lang = $lang;
		$last__in_filename = $filename;
		$last__in_path_idx = $path_idx;

		global $patchwork_lastpath_level;

		$level = PATCHWORK_PATH_LEVEL - $path_idx;

		$lang = resolvePath("public/{$lang}{$filename}", $level);
		$lang_level = $patchwork_lastpath_level;

		if ($l_ng)
		{
			$l_ng = resolvePath("public/{$l_ng}{$filename}", $level);
			if ($patchwork_lastpath_level > $lang_level)
			{
				$lang = $l_ng;
				$lang_level = $patchwork_lastpath_level;
			}
		}

		$l_ng = resolvePath("public/__/{$filename}", $level);
		if ($patchwork_lastpath_level > $lang_level)
		{
			$lang = $l_ng;
			$lang_level = $patchwork_lastpath_level;
		}

		$path_idx = PATCHWORK_PATH_LEVEL - $lang_level;

		$last_out_filename = $lang;
		$last_out_path_idx = $path_idx;

		return $lang;
	}

	static function syncTemplate($template, $ctemplate)
	{
		if (file_exists($ctemplate))
		{
			$template = self::resolvePublicPath($template . '.ptl');
			if ($template && filemtime($ctemplate) <= filemtime($template)) return unlink($ctemplate);
		}
	}
}

class agent
{
	const contentType = 'text/html';

	public $get = array();

	protected

	$template = '',
	$maxage  = 0,
	$expires = 'auto',
	$canPost = false,
	$watch = array(),

	// For convenience only, same content as agent::contentType
	$contentType;


	function control() {}
	function compose($o) {return $o;}
	function getTemplate()
	{
		if ($this->template) return $this->template;

		$class = get_class($this);
		$prev = '';

		do
		{
			$template = strtr(substr($class, 6), '_', '/');
			if ($template !== $prev && false !== p::resolvePublicPath($template . '.ptl')) return $template;
			$prev = $template;
		}
		while ($class = get_parent_class($class));

		return 'bin';
	}

	final public function __construct($args = array())
	{
		$this->contentType = constant(get_class($this) . '::contentType');
		$this->contentType && p::header('Content-Type: ' . $this->contentType);

		$a = (array) $this->get;

		$this->get = (object) array();
		$_GET = array();

		foreach ($a as $key => &$a)
		{
			if (is_string($key))
			{
				$default = $a;
				$a = $key;
			}
			else $default = '';

			$a = explode(':', $a);
			$key = array_shift($a);

			$b = isset($args[$key]) ? (string) $args[$key] : $default;
			if (false !== strpos($b, "\0")) $b = str_replace("\0", '', $b);

			if ($a)
			{
				$b = FILTER::get($b, array_shift($a), $a);
				if (false === $b) $b = $default;
			}

			$_GET[$key] = $this->get->$key = $b;
		}

		$this->control();
	}

	function metaCompose()
	{
		p::setMaxage($this->maxage);
		p::setExpires($this->expires);
		p::watch($this->watch);
		if ($this->canPost) p::canPost();
	}
}

class agentTemplate extends agent
{
	protected

	$maxage = -1,
	$watch = array('public/templates');


	function control() {}
}

class loop
{
	private

	$loopLength = false,
	$filter = array();


	function __construct($filter = '')
	{
		$filter && $this->addFilter($filter);
	}

	protected function prepare() {}
	protected function next() {}

	final public function &loop($escape = false)
	{
		$catchMeta = p::$catchMeta;
		p::$catchMeta = true;

		if ($this->loopLength === false) $this->loopLength = (int) $this->prepare();

		if (!$this->loopLength) $data = false;
		else
		{
			$data = $this->next();
			if ($data || is_array($data))
			{
				$data = (object) $data;
				$i = 0;
				$len = count($this->filter);
				while ($i<$len) $data = (object) call_user_func($this->filter[$i++], $data, $this);
			}
			else $this->loopLength = false;
		}

		p::$catchMeta = $catchMeta;

		if ($escape && !($this instanceof L_) && $data) foreach ($data as &$i) is_string($i) && $i = htmlspecialchars($i);

		return $data;
	}

	final public function addFilter($filter) {if ($filter) $this->filter[] = $filter;}

	final public function __toString()
	{
		$catchMeta = p::$catchMeta;
		p::$catchMeta = true;

		if ($this->loopLength === false) $this->loopLength = (int) $this->prepare();

		p::$catchMeta = $catchMeta;

		return (string) $this->loopLength;
	}

	final public function getLength()
	{
		return (int) $this->__toString();
	}
}

class PrivateDetection extends Exception {}

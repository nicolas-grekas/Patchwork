<?php /***** vi: set encoding=utf-8 expandtab shiftwidth=4: ****************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/



/**** Post-configuration stage 0 ****/

$patchwork_appId = (int) /*<*/sprintf('%020d', patchwork_bootstrapper::$appId)/*>*/;


$CONFIG += array(
    'debug.allowed'  => true,
    'debug.password' => '',
    'debug.scream'   => false,
    'turbo'          => false,
);

defined('DEBUG') || define('DEBUG', $CONFIG['debug.allowed'] && (!$CONFIG['debug.password'] || isset($_COOKIE['debug_password']) && $CONFIG['debug.password'] == $_COOKIE['debug_password']) ? 1 : 0);
defined('TURBO') || define('TURBO', !DEBUG && $CONFIG['turbo']);

unset($CONFIG['debug.allowed'], $CONFIG['debug.password'], $CONFIG['turbo']);


isset($CONFIG['umask']) && umask($CONFIG['umask']);


// file_exists replacement on Windows
// Fix a bug with long file names
// In debug mode, checks if character case is strict.

/**/if (IS_WINDOWS && !function_exists('__patchwork_realpath'))
/**/{
        if (/*<*/PHP_VERSION_ID < 50200/*>*/ || DEBUG)
        {
/**/        /*<*/patchwork_bootstrapper::alias('file_exists',   'patchwork_file_exists',   array('$file'))/*>*/;
/**/        /*<*/patchwork_bootstrapper::alias('is_file',       'patchwork_is_file',       array('$file'))/*>*/;
/**/        /*<*/patchwork_bootstrapper::alias('is_dir',        'patchwork_is_dir',        array('$file'))/*>*/;
/**/        /*<*/patchwork_bootstrapper::alias('is_link',       'patchwork_is_link',       array('$file'))/*>*/;
/**/        /*<*/patchwork_bootstrapper::alias('is_executable', 'patchwork_is_executable', array('$file'))/*>*/;
/**/        /*<*/patchwork_bootstrapper::alias('is_readable',   'patchwork_is_readable',   array('$file'))/*>*/;
/**/        /*<*/patchwork_bootstrapper::alias('is_writable',   'patchwork_is_writable',   array('$file'))/*>*/;

            if (DEBUG)
            {
                function patchwork_file_exists($file)
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
                                if (0 === strcasecmp($file[$i], $realfile[$j]) && !(0 === $i && ':' === substr($file, 1, 1))) trigger_error("Character case mismatch between requested file and its real path ({$file} vs {$realfile})");
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
                function patchwork_file_exists($file) {return file_exists($file) && (!isset($file[99]) || realpath($file));}
            }

            function patchwork_is_file($file)       {return patchwork_file_exists($file) && is_file($file);}
            function patchwork_is_dir($file)        {return patchwork_file_exists($file) && is_dir($file);}
            function patchwork_is_link($file)       {return patchwork_file_exists($file) && is_link($file);}
            function patchwork_is_executable($file) {return patchwork_file_exists($file) && is_executable($file);}
            function patchwork_is_readable($file)   {return patchwork_file_exists($file) && is_readable($file);}
            function patchwork_is_writable($file)   {return patchwork_file_exists($file) && is_writable($file);}
        }
/**/}


function patchwork_class2cache($class, $level)
{
    if (false !== strpos($class, '__x'))
    {
        static $map = array(
            array('__x25', '__x2B', '__x2D', '__x2E', '__x3D', '__x7E'),
            array('%',     '+',     '-',     '.',     '=',     '~'    )
        );

        $class = str_replace($map[0], $map[1], $class);
    }

    $cache = (int) DEBUG . (0>$level ? -$level . '-' : $level);
    $cache = /*<*/patchwork_bootstrapper::$cwd . '.class_'/*>*/
            . strtr($class, '\\', '_') . ".{$cache}.zcache.php";

    return $cache;
}


// __autoload(): the magic part

/**/@copy(patchwork_bootstrapper::$pwd . 'autoloader.php', patchwork_bootstrapper::$cwd . '.patchwork.autoloader.php')
/**/    || @unlink(patchwork_bootstrapper::$cwd . '.patchwork.autoloader.php')
/**/        + copy(patchwork_bootstrapper::$pwd . 'autoloader.php', patchwork_bootstrapper::$cwd . '.patchwork.autoloader.php');
/**/win_hide_file(patchwork_bootstrapper::$cwd . '.patchwork.autoloader.php');

function __autoload($searched_class)
{
    $a = strtr($searched_class, '\\', '_');

    if ($a !== $searched_class && (class_exists($a, false) || interface_exists($a, false)))
    {
/**/    if (function_exists('class_alias'))
            class_alias($a, $searched_class);
        return;
    }

    $a = strtolower($searched_class);

    if ($a !== strtr($a, ";'?.$", '-----')) return;

    if (TURBO && $a =& $GLOBALS['_patchwork_autoloaded'][$a])
    {
        if (is_int($a))
        {
            $b = $a;
            unset($a);
            $a = $b - /*<*/count(patchwork_bootstrapper::$paths) - patchwork_bootstrapper::$last/*>*/;

            $b = strtr($searched_class, '\\', '_');
            $i = strrpos($b, '__');
            false !== $i && isset($b[$i+2]) && '' === trim(substr($b, $i+2), '0123456789') && $b = substr($b, 0, $i);

            $a = $b . '.php.' . DEBUG . (0>$a ? -$a . '-' : $a);
        }

        $a = /*<*/patchwork_bootstrapper::$cwd/*>*/ . ".class_{$a}.zcache.php";

        $GLOBALS["a\x9D"] = false;

        if (file_exists($a))
        {
            patchwork_include($a);

            if (class_exists($searched_class, false) || interface_exists($searched_class, false)) return;
        }
    }

    if (!class_exists('__patchwork_autoloader', false))
    {
        require TURBO
            ? /*<*/patchwork_bootstrapper::$cwd . '.patchwork.autoloader.php'/*>*/
            : /*<*/patchwork_bootstrapper::$pwd . 'autoloader.php'/*>*/;
    }

    __patchwork_autoloader::autoload($searched_class);
}

function &patchwork_autoload_marker($marker, &$ref)
{
    return $ref;
}


// patchworkProcessedPath(): private use for the preprocessor (in files in the include_path)

function patchworkProcessedPath($file, $lazy = false)
{
/**/if (IS_WINDOWS)
        false !== strpos($file, '\\') && $file = strtr($file, '\\', '/');

    if (false !== strpos('.' . $file, './') || (/*<*/IS_WINDOWS/*>*/ && ':' === substr($file, 1, 1)))
    {
/**/if (function_exists('__patchwork_realpath'))
        if ($f = patchwork_realpath($file)) $file = $f;
/**/else
        if ($f = realpath($file)) $file = $f;

        $p =& $GLOBALS['patchwork_path'];

        for ($i = /*<*/patchwork_bootstrapper::$last + 1/*>*/; $i < /*<*/count(patchwork_bootstrapper::$paths)/*>*/; ++$i)
        {
            if (0 === strncmp($file, $p[$i], strlen($p[$i])))
            {
                $file = substr($file, strlen($p[$i]));
                break;
            }
        }

        if (/*<*/count(patchwork_bootstrapper::$paths)/*>*/ === $i) return $f;
    }

    $source = patchworkPath('class/' . $file, $level);

    if (false === $source) return false;

    $cache = patchwork_file2class($file);
    $cache = patchwork_class2cache($cache, $level);

    if (file_exists($cache) && (TURBO || filemtime($cache) > filemtime($source))) return $cache;

    patchwork_preprocessor::execute($source, $cache, $level, false, true, $lazy);

    return $cache;
}



/**** Post-configuration stage 1 ****/



function E() {$a = func_get_args(); $a ? patchwork::log(isset($a[1]) ? $a : $a[0], 0, 0) : patchwork::log(0, 0, 0, 0);}
function strlencmp($a, $b) {return strlen($b) - strlen($a);}


// Fix config

$CONFIG += array(
    'clientside'            => true,
    'i18n.lang_list'        => array(),
    'maxage'                => 2678400,
    'P3P'                   => 'CUR ADM',
    'xsendfile'             => isset($_SERVER['PATCHWORK_XSENDFILE']) ? $_SERVER['PATCHWORK_XSENDFILE'] : false,
    'document.domain'       => '',
    'X-UA-Compatible'       => 'IE=edge,chrome=1',
    'session.save_path'     => /*<*/patchwork_bootstrapper::$zcache/*>*/,
    'session.cookie_path'   => 'auto',
    'session.cookie_domain' => 'auto',
    'session.auth_vars'     => array(),
    'session.group_vars'    => array(),
    'translator.adapter'    => false,
    'translator.options'    => array(),
);


// Prepare for I18N

$a =& $CONFIG['i18n.lang_list'];
$a ? (is_array($a) || $a = explode('|', $a)) : ($a = array('' => '__'));
define('PATCHWORK_I18N', 2 <= count($a));

$b = array();

foreach ($a as $k => &$v)
{
    if (is_int($k))
    {
        $v = (string) $v;

        if (!isset($a[$v]))
        {
            $a[$v] = $v;
            $b[] = preg_quote($v, '#');
        }

        unset($a[$k]);
    }
    else $b[] = preg_quote($v, '#');
}

unset($a, $v);

usort($b, 'strlencmp');
$b = '(' . implode('|', $b) . ')';


/* patchwork's context initialization
*
* Setup needed environment variables if they don't exists :
*   $_SERVER['PATCHWORK_BASE']: application's base part of the url. Lang independant (ex. /myapp/__/)
*   $_SERVER['PATCHWORK_REQUEST']: request part of the url (ex. myagent/mysubagent/...)
*   $_SERVER['PATCHWORK_LANG']: lang (ex. en) if application is internationalized
*/

$a = strpos($_SERVER['REQUEST_URI'], '?');
$a = false === $a ? $_SERVER['REQUEST_URI'] : substr($_SERVER['REQUEST_URI'], 0, $a);
$a = rawurldecode($a);

if (false !== strpos($a, '/.'))
{
    $j = explode('/', substr($a, 1));
    $r = array();
    $v = false;

    foreach ($j as $j) switch ($j)
    {
    case '..': $r && array_pop($r);
    case '.' : $v = true; break;
    default  : $r[] = rawurlencode($j);
    }

    if ($v)
    {
        $r = '/' . ($r ? implode('/', $r) . ('.' === $j || '..' === $j ? '/' : '') : '');
        '' !== $_SERVER['QUERY_STRING'] && $r .= '?' . $_SERVER['QUERY_STRING'];
        patchwork_bad_request("Please resolve references to '.' and '..' before issuing your request.", $r);
    }
}

/**/$a = true;
/**/
/**/switch (true)
/**/{
/**/case isset($_SERVER['REDIRECT_PATCHWORK_REQUEST']):
/**/case isset($_SERVER['PATCHWORK_REQUEST'])         :
/**/case isset($_SERVER['ORIG_PATH_INFO'])            :
/**/case isset($_SERVER['PATH_INFO'])                 : break;
/**/
/**/default:
/**/    // Check if the webserver supports PATH_INFO
/**/
/**/    $h = patchwork_http_socket($_SERVER['SERVER_ADDR'], $_SERVER['SERVER_PORT'], isset($_SERVER['HTTPS']));
/**/
/**/    $a = strpos($_SERVER['REQUEST_URI'], '?');
/**/    $a = false === $a ? $_SERVER['REQUEST_URI'] : substr($_SERVER['REQUEST_URI'], 0, $a);
/**/    '/' === substr($a, -1) && $a .= basename(isset($_SERVER['ORIG_SCRIPT_NAME']) ? $_SERVER['ORIG_SCRIPT_NAME'] : $_SERVER['SCRIPT_NAME']);
/**/
/**/    $a  = "GET {$a}/:?p:=exit HTTP/1.0\r\n";
/**/    $a .= "Host: {$_SERVER['HTTP_HOST']}\r\n";
/**/    $a .= "Connection: close\r\n\r\n";
/**/
/**/    fwrite($h, $a);
/**/    $a = fgets($h, 14);
/**/    fclose($h);
/**/
/**/    $a = strpos($a, ' 200');
/**/}
/**/
/**/if ($a)
/**/{
        switch (true)
        {
        case isset($_SERVER['REDIRECT_PATCHWORK_REQUEST']): $r = $_SERVER['REDIRECT_PATCHWORK_REQUEST']; break;
        case isset($_SERVER['PATCHWORK_REQUEST'])         : $r = $_SERVER['PATCHWORK_REQUEST']         ; break;
        case isset($_SERVER['ORIG_PATH_INFO'])            : $r = $_SERVER['ORIG_PATH_INFO']            ; break;
        case isset($_SERVER['PATH_INFO'])                 : $r = $_SERVER['PATH_INFO']                 ; break;

        case '/' === substr($a, -1): $a .= basename(isset($_SERVER['ORIG_SCRIPT_NAME']) ? $_SERVER['ORIG_SCRIPT_NAME'] : $_SERVER['SCRIPT_NAME']);
        default: $r = '';
        }

        $a .= '/';
/**/}
/**/else
/**/{
        $r = $_SERVER['QUERY_STRING'];
        $j = strpos($r, '?');
        false !== $j || $j = strpos($r, '&');

        if (false !== $j)
        {
            $r = substr($r, 0, $j);
            $_SERVER['QUERY_STRING'] = substr($_SERVER['QUERY_STRING'], $j+1);

            parse_str($_SERVER['QUERY_STRING'], $_GET);

/**/        if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc())
/**/        {
                $k = array(&$_GET);
                for ($i = 0, $j = 1; $i < $j; ++$i)
                {
                    foreach ($k[$i] as &$v)
                    {
                        if (is_array($v)) $k[$j++] =& $v;
                        else
                        {
/**/                        if (ini_get_bool('magic_quotes_sybase'))
                                $v = str_replace("''", "'", $v);
/**/                        else
                                $v = stripslashes($v);
                        }
                    }

                    reset($k[$i]);
                    unset($k[$i]);
                }

                unset($k, $v);
/**/        }
        }
        else if ('' !== $r)
        {
            $_SERVER['QUERY_STRING'] = '';

            reset($_GET);
            $j = key($_GET);
            unset($_GET[$j]);
        }

        $j = explode('/', urldecode($r));
        $r = array();
        $v = 0;

        foreach ($j as $j)
        {
            if ('.' === $j) continue;
            if ('..' === $j) $r ? array_pop($r) : ++$v;
            else $r[]= $j;
        }

        $r = implode('/', $r);

        if ($v)
        {
            '/' !== substr($a, -1) && $a .= '/';
            $a = preg_replace("'[^/]*/{1,{$v}}$'", '', $a);
            '' === $a && $a = '/';
            $a = str_replace('%2F', '/', rawurlencode($a . $r));
            '' !== $_SERVER['QUERY_STRING'] && $a .= '?' . $_SERVER['QUERY_STRING'];

            header('HTTP/1.1 301 Moved Permanently');
            header('Location: ' . $a);

            exit;
        }
/**/}

$r = preg_replace("'/[./]*/'", '/', '/' . $r . '/');
$a = preg_replace("'/[./]*/'", '/', '/' . $a);

/**/if ($a && IS_WINDOWS)
/**/{
        // Workaround for http://bugs.php.net/44001

        if ('/' !== $r && false !== strpos($a, './') && false === strpos($r, './'))
        {
            $r = explode('/', $r);
            $j = count($r) - 1;

            $a = explode('/', strrev($a), $j);

            for ($i = 0; $i < $j; ++$i) $r[$j - $i] .= str_repeat('.', strspn($a[$i], '.'));

            $a = strrev(implode('/', $a));
            $r = implode('/', $r);
        }
/**/}

$_SERVER['PATCHWORK_REQUEST'] = (string) substr($r, 1, -1);

isset($_SERVER['REDIRECT_PATCHWORK_BASE']) && $_SERVER['PATCHWORK_BASE'] = $_SERVER['REDIRECT_PATCHWORK_BASE'];
isset($_SERVER['REDIRECT_PATCHWORK_LANG']) && $_SERVER['PATCHWORK_LANG'] = $_SERVER['REDIRECT_PATCHWORK_LANG'];

if (isset($_SERVER['PATCHWORK_BASE']))
{
    if (0 === strncmp($_SERVER['PATCHWORK_BASE'], '/', 1)) $_SERVER['PATCHWORK_BASE'] = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PATCHWORK_BASE'];

    if (!isset($_SERVER['PATCHWORK_LANG']))
    {
        $k = explode('__', $_SERVER['PATCHWORK_BASE'], 2);
        if (2 === count($k))
        {
            $k = '#' . preg_quote($k[0], '#') . $b . '#';
            preg_match($k, 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $a, $k)
                && $_SERVER['PATCHWORK_LANG'] = (string) array_search($k[1], $CONFIG['i18n.lang_list']);
        }
        else if (PATCHWORK_I18N) switch (substr($_SERVER['PATCHWORK_BASE'], -1))
        {
        case '/': 
        case '?': $_SERVER['PATCHWORK_BASE'] .= '__/'; break;
        default:
/**/        if ($a)
                $_SERVER['PATCHWORK_BASE'] .= '/__/';
/**/        else
                $_SERVER['PATCHWORK_BASE'] .= '?__/';
        }
    }
}
else
{
    $a = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $a;

/**/if ($a)
        $_SERVER['PATCHWORK_BASE'] = substr($a, 0, -strlen($r)) . '/' . (PATCHWORK_I18N ? '__/' : '');
/**/else
        $_SERVER['PATCHWORK_BASE'] = $a . '?' . (PATCHWORK_I18N ? '__/' : '');
}

if (isset($_SERVER['PATCHWORK_LANG']))
{
    $a =& $CONFIG['i18n.lang_list'];
    $b =& $_SERVER['PATCHWORK_LANG'];

    isset($a[$b]) || $b = (string) array_search($b, $a);

    unset($a, $b);
}
else if ('__/' === substr($_SERVER['PATCHWORK_BASE'], -3) && preg_match("#^/{$b}/#", $r, $a))
{
    $_SERVER['PATCHWORK_LANG'] = array_search($a[1], $CONFIG['i18n.lang_list']);
    $_SERVER['PATCHWORK_REQUEST'] = (string) substr($r, strlen($a[1])+2, -1);
}
else $_SERVER['PATCHWORK_LANG'] = '';

reset($CONFIG['i18n.lang_list']);
PATCHWORK_I18N || $_SERVER['PATCHWORK_LANG'] = key($CONFIG['i18n.lang_list']);

$a = 'auto' === $CONFIG['session.cookie_path'];
$b = 'auto' === $CONFIG['session.cookie_domain'];

if ($a || $b)
{
    if (preg_match("'^(https?://)([^/:]+)(\.?(?::[^/_]*)?)(/(?:[^?#/]*/)*)'", $_SERVER['PATCHWORK_BASE'], $k))
    {
        if ($k[0] = strrpos($k[0], '__'))
        {
            $k[0] -= strlen($k[1]);
            $k[1]  = strlen($k[2]);
        }
        else $k[1] = 0;

        if ($a)
        {
            if ($k[0] >= $k[1])
            {
                $k[4] = substr($k[4], 0, $k[0] - $k[1] - strlen($k[3]));
                $a = strrpos($k[4], '/');
                $CONFIG['session.cookie_path'] = $a ? substr($k[4], 0, $a) : '/';
            }
            else $CONFIG['session.cookie_path'] = $k[4];
        }

        if ($b)
        {
            if ($k[0] < $k[1])
            {
                $k[2] = substr($k[2], $k[0]+2);
                $a = strpos($k[2], '.');
                $CONFIG['session.cookie_domain'] = false !== $a ? substr($k[2], $a) : '';
            }
            else $CONFIG['session.cookie_domain'] = '';
        }

        unset($k);
    }
    else
    {
        $a
            ? ($CONFIG['session.cookie_path']   = '/')
            : ($CONFIG['session.cookie_domain'] = '' );
    }
}

/**/ /*<*/patchwork_bootstrapper::alias('w', 'trigger_error', array('$msg', '$type' => E_USER_NOTICE))/*>*/;
/**/ /*<*/patchwork_bootstrapper::alias('header'      , 'patchwork::header',       array('$s', '$replace' => true, '$response_code' => null))/*>*/;
/**/ /*<*/patchwork_bootstrapper::alias('setcookie'   , 'patchwork::setcookie',    array('$name', '$value' => '', '$expires' => 0, '$path' => '', '$domain' => '', '$secure' => false, '$httponly' => false))/*>*/;
/**/ /*<*/patchwork_bootstrapper::alias('setcookieraw', 'patchwork::setcookieraw', array('$name', '$value' => '', '$expires' => 0, '$path' => '', '$domain' => '', '$secure' => false, '$httponly' => false))/*>*/;

if (strtr($_SERVER['PATCHWORK_BASE'], '<>&"', '----') !== $_SERVER['PATCHWORK_BASE'])
{
    die('Patchwork error: Base URL can not contain special HTML character (' . htmlspecialchars($_SERVER['PATCHWORK_BASE']) . ')');
}


// Database sugar
function DB($dsn = null)
{
    static $db = array();
    empty($db[$dsn]) && $db[$dsn] = adapter_DB::connect(null === $dsn ? $GLOBALS['CONFIG']['DSN'] : $dsn);
    return $db[$dsn];
}

// PHP session mechanism overloading
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

// Shortcut for applications developers
if ($_SERVER['PATCHWORK_LANG'])
{
    function T($string, $lang = false)
    {
        if (!$lang) $lang = patchwork::__LANG__();
        return TRANSLATOR::get($string, $lang, true);
    }
}
else
{
    function T($string) {return $string;}
}

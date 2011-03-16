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


class patchwork_bootstrapper_bootstrapper
{
    protected

    $marker,
    $cwd,
    $dir,
    $preprocessor,
    $configCode = array(),
    $fSlice, $rSlice,
    $lock = null,
    $file,
    $alias,
    $callerRx;


    function __construct(&$cwd)
    {
        $this->marker = md5(mt_rand(1, mt_getrandmax()));
        $this->cwd    =& $cwd;
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

    function isPathInfoSupported()
    {
        switch (true)
        {
        case isset($_SERVER['REDIRECT_PATCHWORK_REQUEST']):
        case isset($_SERVER['PATCHWORK_REQUEST']):
        case isset($_SERVER['ORIG_PATH_INFO']):
        case isset($_SERVER['PATH_INFO']): return true;
        }

        // Check if the webserver supports PATH_INFO

        $h = patchwork_http_socket($_SERVER['SERVER_ADDR'], $_SERVER['SERVER_PORT'], isset($_SERVER['HTTPS']));

        $a = strpos($_SERVER['REQUEST_URI'], '?');
        $a = false === $a ? $_SERVER['REQUEST_URI'] : substr($_SERVER['REQUEST_URI'], 0, $a);
        '/' === substr($a, -1) && $a .= basename(isset($_SERVER['ORIG_SCRIPT_NAME']) ? $_SERVER['ORIG_SCRIPT_NAME'] : $_SERVER['SCRIPT_NAME']);

        $a  = "GET {$a}/:?p:=exit HTTP/1.0\r\n";
        $a .= "Host: {$_SERVER['HTTP_HOST']}\r\n";
        $a .= "Connection: close\r\n\r\n";

        fwrite($h, $a);
        $a = fgets($h, 14);
        fclose($h);

        return (bool) strpos($a, ' 200');
    }

    protected function initialize($caller)
    {
        @set_time_limit(0);

        $this->callerRx = preg_quote($caller, '/');
        ob_start(array($this, 'ob_lock'));
        ob_start(array($this, 'ob_eval'));

        $caller = array(dirname($caller) . '/');
        $this->loadConfig($caller, 'common');
        $this->preprocessor = $this->getPreprocessor();

        $this->alias =& $GLOBALS['patchwork_preprocessor_alias'];
        $this->alias = array();
    }

    function ob_eval($buffer)
    {
        return '' !== $buffer
            ? preg_replace("/{$this->callerRx}\(\d+\) : eval\(\)\'d code/", $this->file, $buffer)
            : '';
    }

    function ob_lock($buffer)
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
            file_put_contents("{$this->cwd}.patchwork.alias.ser", serialize($this->alias));

            $a = array(
                "<?php \$c\x9D=&\$_patchwork_autoloaded;",
                "\$c\x9D=array();",
                "\$d\x9D=1;",
                "(\$e\x9D=\$b\x9D=\$a\x9D=__FILE__.'*" . mt_rand(1, mt_getrandmax()) . "')&&\$d\x9D&&0;",
            );

            foreach ($this->configCode as $code)
            {
                if (false !== strpos($code, "/*{$this->marker}:"))
                {
                    $code = preg_replace(
                        "#/\\*{$this->marker}:(\d+)(.+?)\\*/#",
                        "global \$a\x9D,\$c\x9D;isset(\$c\x9D['$2'])||\$a\x9D=__FILE__.'*$1';",
                        str_replace("/*{$this->marker}:*/", '', $code)
                    );
                }

                $a[] = $code;
            }

            patchworkPath('class/patchwork.php', $level);
            $b = addslashes("{$this->cwd}.class_patchwork.php.0{$level}.zcache.php");
            $a[] = "DEBUG || file_exists('{$b}') && include '{$b}';";
            $a[] = "patchwork::start();";
            $a[] = "exit;"; // When php.ini's output_buffering is on, the buffer is sometimes not flushed...

            $a = implode('', $a);

            fwrite($this->lock, $a);
            fclose($this->lock);

            $b = $this->getLockFile();

            touch($b, $_SERVER['REQUEST_TIME'] + 1);
            win_hide_file($b);
            rename($b, $this->getCompiledFile());

            $this->lock = $this->configCode = $this->fSlice = $this->rSlice = null;

            @set_time_limit(ini_get('max_execution_time'));
        }
        else
        {
            echo $buffer;

            $buffer = $this->getEchoError($this->file, 0, $buffer, 'during bootstrap');

            die("\n<br><br>\n\n<small>&mdash; {$buffer}. Dying &mdash;</small>");
        }
    }

    function preprocessorPass1()
    {
        return $this->preprocessor->staticPass1($this->file);
    }

    function preprocessorPass2()
    {
        $code = $this->preprocessor->staticPass2();
        ob_get_length() && $this->release();
        return $this->configCode[$this->file] = $code;
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
                $found = "{$paths[$i]}zcache" . DIRECTORY_SEPARATOR;

                if (@touch("{$found}.patchwork.writeTest")) @unlink("{$found}.patchwork.writeTest");
                else $found = false;

                break;
            }
        }

        if (!$found)
        {
            $found = "{$paths[0]}zcache" . DIRECTORY_SEPARATOR;
            file_exists($found) || mkdir($found);
        }

        return $found;
    }


    function initConfig()
    {
        // Purge old code files

        if (!file_exists($a = "{$this->cwd}.zcache.php"))
        {
            touch($a);
            win_hide_file($a);

            $h = opendir($this->cwd);
            while (false !== $a = readdir($h))
            {
                if ('.zcache.php' === substr($a, -11) && '.' === $a[0]) @unlink($this->cwd . $a);
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
        if (function_exists($function))
        {
            $inline = $function == $alias ? -1 : 2;
            $function = "__patchwork_{$function}";
        }
        else
        {
            $inline = 1;

            if ($function == $alias)
            {
                return "die('Patchwork error: Circular aliasing of function {$function}() in ' . __FILE__ . ' on line ' . __LINE__);";
            }
        }

        $args = array($args, array(), array());

        foreach ($args[0] as $k => $v)
        {
            if (is_string($k))
            {
                $k = trim(strtr($k, "\n\r", '  '));
                $args[1][] = $k . '=' . patchwork_PHP_Parser::export($v);
                0 > $inline && $inline = 0;
            }
            else
            {
                $k = trim(strtr($v, "\n\r", '  '));
                $args[1][] = $k;
            }

            $v = '[a-zA-Z_\x7F-\xFF][a-zA-Z0-9_\x7F-\xFF]*';
            $v = "'^(?:(?:(?: *\\\\ *)?{$v})+(?:&| +&?)|&?) *(\\\${$v})$'D";

            if (!preg_match($v, $k, $v))
            {
                1 !== $inline && $function = substr($function, 12);
                return "die('Patchwork error: Invalid parameter for {$function}()\'s alias ({$alias}: {$k}) in ' . __FILE__);";
            }

            $args[2][] = $v[1];
        }

        $args[1] = implode(',', $args[1]);
        $args[2] = implode(',', $args[2]);

        $inline && $this->alias[1 !== $inline ? substr($function, 12) : $function] = $alias;

        $inline = explode('::', $alias, 2);
        $inline = 2 === count($inline) ? mt_rand(1, mt_getrandmax()) . strtolower($inline[0]) : '';

        // FIXME: when aliasing a user function, this will throw a can not redeclare fatal error!
        // Some help is required from the main preprocessor to rename aliased user functions.
        // When done, aliasing will be perfect for user functions. For internal functions,
        // the only uncatchable case would be when using an internal caller (especially objects)
        // with an internal callback. This also means that functions with callback could be left
        // untracked, at least when we are sure that an internal function will not be used as a callback.

        return $return_ref
            ? "function &{$function}({$args[1]}) {/*{$this->marker}:{$inline}*/\${''}=&{$alias}({$args[2]});return \${''}}"
            : "function  {$function}({$args[1]}) {/*{$this->marker}:{$inline}*/return {$alias}({$args[2]});}";
    }

    protected function getEchoError($file, $line, $what, $when)
    {
        if ($len = strlen($what))
        {
            if ('' === trim($what))
            {
                $what = $len > 1 ? "{$len} bytes of whitespace have" : 'One byte of whitespace has';
            }
            else if (0 === strncmp($what, "\xEF\xBB\xBF", 3))
            {
                $what = 'An UTF-8 byte order mark (BOM) has';
            }
            else
            {
                $what = $len > 1 ? "{$len} bytes have" : 'One byte has';
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

        $this->file = $file;

        return true;
    }

    protected function getPreprocessor()
    {
        require $this->dir . '/preprocessor.php';

        return new patchwork_bootstrapper_preprocessor;
    }

    protected function getInheritance()
    {
        require $this->dir . '/inheritance.php';

        return new patchwork_bootstrapper_inheritance;
    }

    protected function getUpdatedb()
    {
        require $this->dir . '/updatedb.php';

        return new patchwork_bootstrapper_updatedb;
    }
}

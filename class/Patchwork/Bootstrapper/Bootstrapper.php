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


class Patchwork_Bootstrapper_Bootstrapper
{
    protected

    $marker,
    $cwd,
    $dir,
    $preprocessor,
    $step = array(),
    $code = array(),
    $nextStep,
    $lock = null,
    $file,
    $overrides,
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
    function getBootstrapper() {return $this->cwd . '.patchwork.php';}
    function getLockFile()     {return $this->cwd . '.patchwork.lock';}

    function initLock($caller, $retry = true)
    {
        $lock = $this->getLockFile();
        $file = $this->getBootstrapper();

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
            return $this->initLock($caller, false);
        }
        else return false;
    }

    function isReleased()
    {
        return !$this->lock;
    }

    protected function initialize($caller)
    {
        @set_time_limit(0);

        $this->callerRx = preg_quote($caller, '/');

        $this->preprocessor = $this->getPreprocessor();

        $this->overrides =& $GLOBALS['patchwork_preprocessor_overrides'];
        $this->overrides = array();

        $this->step[] = array(null, dirname($caller) . '/' . 'common.patchwork.php');
        $this->step[] = array("<?php \n/**/Patchwork_Bootstrapper::initInheritance();Patchwork_Bootstrapper::initZcache();\n", null);

        ob_start(array($this, 'ob_lock'));
        ob_start(array($this, 'ob_eval'));
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

    function loadNextStep()
    {
        ob_flush();
        return null !== $this->nextStep = array_shift($this->step);
    }

    function release()
    {
        ob_end_flush();

        if ('' === $buffer = ob_get_clean())
        {
            file_put_contents("{$this->cwd}.patchwork.overrides.ser", serialize($this->overrides));

            $a = array(
                "<?php \$c\x9D=array();",
                "\$d\x9D=1;",
                "(\$e\x9D=\$b\x9D=\$a\x9D=__FILE__.'*" . mt_rand(1, mt_getrandmax()) . "')&&\$d\x9D&&0;",
            );

            foreach ($this->code as $code)
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

            patchworkPath('class/Patchwork.php', $level);
            $b = addslashes("{$this->cwd}.class_Patchwork.php.0{$level}.zcache.php");
            $a[] = "DEBUG || file_exists('{$b}') && include '{$b}';";
            $a[] = "Patchwork::start();";
            $a[] = "exit;"; // When php.ini's output_buffering is on, the buffer is sometimes not flushed...

            $a = implode('', $a);

            fwrite($this->lock, $a);
            fclose($this->lock);

            $b = $this->getLockFile();

            touch($b, $_SERVER['REQUEST_TIME'] + 1);
            win_hide_file($b);
            rename($b, $this->getBootstrapper());

            $this->lock = $this->code = null;

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
        list($code, $this->file) = $this->nextStep;
        null === $code && $code = file_get_contents($this->file);
        return $this->preprocessor->staticPass1($code, $this->file);
    }

    function preprocessorPass2()
    {
        $code = $this->preprocessor->staticPass2();
        ob_get_length() && $this->release();
        return $this->code[] = $code;
    }

    function getLinearizedInheritance($pwd)
    {
        $a = $this->getInheritance()->linearizeGraph($pwd, $this->cwd);

        $b = array_slice($a[0], 0, $a[1] + 1);

        foreach (array_reverse($b) as $c)
            if (file_exists($c .= 'bootup.patchwork.php'))
                $this->step[] = array(null, $c);

        $this->step[] = array("<?php \n/**/Patchwork_Bootstrapper::initConfig();\n", null);

        foreach ($b as $c)
            if (file_exists($c .= 'config.patchwork.php'))
                $this->step[] = array(null, $c);

        $this->step[] = array("<?php \n/**/class_exists('Patchwork', true);Patchwork_Setup::hook();\n", null);

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

        $GLOBALS["c\x9D"] = array();
        $GLOBALS["b\x9D"] = $GLOBALS["a\x9D"] = false;
    }

    function updatedb($paths, $last, $zcache)
    {
        return $this->getUpdatedb()->buildPathCache($paths, $last, $this->cwd, $zcache);
    }

    function override($function, $override, $args, $return_ref = false)
    {
        ':' === substr($override, 0, 1) && $override = 'Patchwork_PHP_Override_' . substr($override, 1);
        ':' === substr($override, -1)   && $override .= ':' . $function;
        $override = ltrim($override, '\\');

        if (function_exists($function))
        {
            $inline = 0 === strcasecmp($function, $override) ? -1 : 2;
            $function = "__patchwork_{$function}";
        }
        else
        {
            $inline = 1;

            if (0 === strcasecmp($function, $override))
            {
                return "die('Patchwork error: Circular overriding of function {$function}() in ' . __FILE__ . ' on line ' . __LINE__);";
            }
        }

        $args = array($args, array(), array());

        foreach ($args[0] as $k => $v)
        {
            if (is_string($k))
            {
                $k = trim(strtr($k, "\n\r", '  '));
                $args[1][] = $k . '=' . Patchwork_PHP_Parser::export($v);
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
                return "die('Patchwork error: Invalid parameter for {$function}()\'s override ({$override}: {$k}) in ' . __FILE__);";
            }

            $args[2][] = $v[1];
        }

        $args[1] = implode(',', $args[1]);
        $args[2] = implode(',', $args[2]);

        $inline && $this->overrides[1 !== $inline ? substr($function, 12) : $function] = $override;

        $inline = explode('::', $override, 2);
        $inline = 2 === count($inline) ? mt_rand(1, mt_getrandmax()) . strtolower(strtr($inline[0], '\\', '_')) : '';

        // FIXME: when overriding a user function, this will throw a can not redeclare fatal error!
        // Some help is required from the main preprocessor to rename overridden user functions.
        // When done, overriding will be perfect for user functions. For internal functions,
        // the only uncatchable case would be when using an internal caller (especially objects)
        // with an internal callback. This also means that functions with callback could be left
        // untracked, at least when we are sure that an internal function will not be used as a callback.

        return $return_ref
            ? "function &{$function}({$args[1]}) {/*{$this->marker}:{$inline}*/\${''}=&{$override}({$args[2]});return \${''}}"
            : "function  {$function}({$args[1]}) {/*{$this->marker}:{$inline}*/return {$override}({$args[2]});}";
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

    protected function getPreprocessor()
    {
        require $this->dir . '/Preprocessor.php';

        return new Patchwork_Bootstrapper_Preprocessor;
    }

    protected function getInheritance()
    {
        require $this->dir . '/Inheritance.php';

        return new Patchwork_Bootstrapper_Inheritance;
    }

    protected function getUpdatedb()
    {
        require $this->dir . '/Updatedb.php';

        return new Patchwork_Bootstrapper_Updatedb;
    }
}

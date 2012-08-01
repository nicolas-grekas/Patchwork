<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class Patchwork_Bootstrapper_Manager
{
    protected

    $pwd,
    $cwd,
    $base,
    $paths,
    $zcache,
    $last,

    $bootstrapper,
    $preprocessor,
    $lock = null,
    $steps = array(),
    $substeps = array(),
    $file,
    $overrides = array(array(), array()),
    $callerRx;


    function __construct($bootstrapper, $caller, $pwd, $cwd)
    {
        $cwd = (empty($cwd) ? '.' : rtrim($cwd, '/\\')) . DIRECTORY_SEPARATOR;

        $this->bootstrapper = $bootstrapper;
        $this->callerRx = preg_quote($caller, '/');
        $this->pwd = $pwd;
        $this->cwd = $cwd;
        $this->base = dirname($caller) . DIRECTORY_SEPARATOR;

        switch (true)
        {
        case isset($_GET['p:']) && 'exit' === $_GET['p:']:
            die('Exit requested');
        case !function_exists('token_get_all'):
            throw $this->error('Extension "tokenizer" is needed and not loaded');
        case !file_exists($cwd . 'config.patchwork.php'):
            throw $this->error("File config.patchwork.php not found in {$cwd}. Did you set PATCHWORK_BOOTPATH correctly?");
        case function_exists('__autoload') && !function_exists('spl_autoload_register'):
            throw $this->error('__autoload() is enabled and spl_autoload_register() is not available');
        case function_exists('mb_internal_encoding'):
            mb_internal_encoding('8bit'); // if mbstring overloading is enabled
            ini_set('mbstring.internal_encoding', '8bit');
        }

        if ($this->getLock(true))
        {
            $s = '$CONFIG = array();';

            if (function_exists('apc_clear_cache')) apc_clear_cache();

            // Turn off magic quotes runtime

            if (function_exists('get_magic_quotes_runtime') && @get_magic_quotes_runtime())
            {
                @set_magic_quotes_runtime(false);
                if (@get_magic_quotes_runtime())
                    throw $this->error('Failed to turn off magic_quotes_runtime');

                $s .= "@set_magic_quotes_runtime(false);";
            }

            // Register static constants

            $v = get_defined_constants(true);
            unset($v['user']);

            foreach ($v as $v)
                foreach (array_keys($v) as $v)
                    $this->overrides[1][] = $v;

            // Backport PHP_VERSION_ID and co.

            if (!defined('PHP_VERSION_ID'))
            {
                $v = array_map('intval', explode('.', PHP_VERSION, 3));
                $s .= "define('PHP_VERSION_ID',"      . (10000 * $v[0] + 100 * $v[1] + $v[2]) . ");";
                $s .= "define('PHP_MAJOR_VERSION',"   . $v[0] . ");";
                $s .= "define('PHP_MINOR_VERSION',"   . $v[1] . ");";
                $s .= "define('PHP_RELEASE_VERSION'," . $v[2] . ");";

                $v = substr(PHP_VERSION, strlen(implode('.', $v)));
                $s .= "define('PHP_EXTRA_VERSION','" . addslashes(false !== $v ? $v : '') . "');";

                $v = array('PHP_VERSION_ID','PHP_MAJOR_VERSION','PHP_MINOR_VERSION','PHP_RELEASE_VERSION','PHP_EXTRA_VERSION');
                foreach ($v as $v) $this->overrides[1][] = $v;
            }

            if (!defined('E_DEPRECATED'))
            {
                $s .= "define('E_DEPRECATED'," . E_NOTICE . ");";
                $s .= "define('E_USER_DEPRECATED'," . E_USER_NOTICE . ");";
                $v = array('E_DEPRECATED','E_USER_DEPRECATED');
                foreach ($v as $v) $this->overrides[1][] = $v;
            }

            // Register the next steps

            $s && $this->steps[] = array($s, __FILE__);
            $this->steps[] = array(array($this, 'initAutoloader'  ), null);
            $this->steps[] = array(array($this, 'initPreprocessor'), null);
            $this->steps[] = array(null, $this->pwd . 'bootup.patchwork.php');
            $this->steps[] = array(array($this, 'initInheritance' ), null);
            $this->steps[] = array(array($this, 'initZcache'      ), null);
            $this->steps[] = array(array($this, 'exportPathData'  ), null);

            @set_time_limit(0);

            if (headers_sent($file, $line) || ob_get_length())
                throw $this->error($this->buildEchoErrorMsg($file, $line, ob_get_flush(), 'before bootstrap'));

            ob_start(array($this, 'ob_eval'));
        }
        else
        {
            $this->steps[] = array("require {$this->cwd}.patchwork.php; return false;", __FILE__);
        }
    }

    protected function getLock($retry)
    {
        $lock = $this->cwd . '.patchwork.lock';
        $file = $this->cwd . '.patchwork.php';

        if ($this->lock = @fopen($lock, 'xb'))
        {
            if (file_exists($file))
            {
                fclose($this->lock);
                $this->lock = null;
                @unlink($lock);
                if ($retry)
                {
                    $file = $this->getBestPath($file);

                    throw $this->error("File {$file} exists. Please fix your front bootstrap file.");
                }
                else return false;
            }

            flock($this->lock, LOCK_EX);
            fwrite($this->lock, '<?php ');

            return true;
        }
        else if ($h = $retry ? @fopen($lock, 'rb') : fopen($lock, 'rb'))
        {
            usleep(1000);
            flock($h, LOCK_SH);
            flock($h, LOCK_UN);
            fclose($h);
            file_exists($file) || usleep(1000);
        }
        else if ($retry)
        {
            $dir = dirname($lock);

            if (@!(touch($dir . '/.patchwork.touch') && unlink($dir . '/.patchwork.touch')))
            {
                $dir = $this->getBestPath($dir);

                throw $this->error("Please change the permissions of the {$dir} directory so that the current process can write in it.");
            }
        }

        if ($retry && !file_exists($file))
        {
            @unlink($lock);
            return $this->getLock(false);
        }
        else return false;
    }

    function getNextStep()
    {
        for (;;)
        {
            if ($this->substeps)
            {
                $this->steps = array_merge($this->substeps, $this->steps);
                $this->substeps = array();
            }
            else if (!$this->steps)
            {
                $this->release();
                return '';
            }

            if (function_exists('patchwork_include'))
            {
                $code = 'spl_autoload_register';
                function_exists('__patchwork_' . $code) && $code = '__patchwork_' . $code;
                $code(array($this, 'autoload'));
            }

            list($code, $this->file) = array_shift($this->steps);

            if (null === $this->file)
            {
                call_user_func($code);
                continue;
            }
            else if (empty($this->preprocessor))
            {
                null === $code && $code = substr(file_get_contents($this->file), 5);
                $this->lock && fwrite($this->lock, $code);
            }
            else
            {
                $code = null !== $code ? '<?php ' . $code : file_get_contents($this->file);
                $code = $this->preprocessor->staticPass1($code, $this->file) .
                    ";return eval('' . {$this->bootstrapper}::\$manager->preprocessorPass2());";
            }

            return $code;
        }
    }

    function preprocessorPass2()
    {
        $code = $this->preprocessor->staticPass2();
        '' === $code && $code = ' ';
        fwrite($this->lock, $code);
        ob_get_length() && $this->release();
        $a = 'spl_autoload_unregister';
        function_exists('__patchwork_' . $a) && $a = '__patchwork_' . $a;
        $a(array($this, 'autoload'));
        return $code;
    }

    function autoload($class)
    {
        $class = $this->pwd . 'class/' . strtr($class, '\\_', '//') . '.php';
        file_exists($class) && patchwork_include($class);
    }

    function ob_eval($buffer)
    {
        return preg_replace("/{$this->callerRx}\(\d+\) : eval\(\)'d code/", $this->file, $buffer);
    }

    protected function release()
    {
        if (headers_sent($file, $line) || ob_get_length())
            throw $this->error($this->buildEchoErrorMsg($this->file, $line, ob_get_flush(), 'during bootstrap'));

        file_put_contents("{$this->cwd}.patchwork.overrides.ser", serialize($this->preprocessor->getOverrides()));
        flock($this->lock, LOCK_UN);
        fclose($this->lock);
        $this->lock = null;

        file_exists($a = $this->cwd . '.patchwork.lock') && rename($a, $this->cwd . '.patchwork.php');

        $a = 'spl_autoload_unregister';
        function_exists('__patchwork_' . $a) && $a = '__patchwork_' . $a;
        function_exists($a) && $a(array($this, 'autoload'));

        @set_time_limit(ini_get('max_execution_time'));
    }

    protected function initAutoloader()
    {
        function_exists('__autoload') && $this->substeps[] = array("spl_autoload_register('__autoload');", __FILE__);

        if (PHP_VERSION_ID < 50300 || !function_exists('spl_autoload_register'))
        {
            // Before PHP 5.3, backport spl_autoload_register()'s $prepend argument
            // and workaround http://bugs.php.net/44144

            $this->substeps[] = array(null, dirname($this->pwd) . '/compat/class/Patchwork/PHP/Override/SplAutoload.php');
            $this->substeps[] = array(
                $this->functionOverride('__autoload',              ':SplAutoload::spl_autoload_call', array('$class')) .
                $this->functionOverride('spl_autoload_call',       ':SplAutoload:', array('$class')) .
                $this->functionOverride('spl_autoload_functions',  ':SplAutoload:', array()) .
                $this->functionOverride('spl_autoload_register',   ':SplAutoload:', array('$callback', '$throw' => true, '$prepend' => false)) .
                $this->functionOverride('spl_autoload_unregister', ':SplAutoload:', array('$callback')) .
                (function_exists('spl_autoload_register')
                    ? "spl_autoload_register(array('Patchwork_PHP_Override_SplAutoload','spl_autoload_call'));"
                    : 'class LogicException extends Exception {}'),
                __FILE__
            );
        }
        else
        {
            $this->substeps[] = array($this->functionOverride('__autoload', 'spl_autoload_call', array('$class')), __FILE__);
        }

        $this->substeps[] = array('function patchwork_include() {return include func_get_arg(0);}', __FILE__);
    }

    protected function initPreprocessor()
    {
        $p = $this->bootstrapper . '_Preprocessor';
        $this->preprocessor = new $p($this->overrides);
        file_exists("{$this->cwd}.patchwork.overrides.ser") && unlink("{$this->cwd}.patchwork.overrides.ser");
    }

    protected function initInheritance()
    {
        $this->cwd = rtrim(patchwork_realpath($this->cwd), '/\\') . DIRECTORY_SEPARATOR;

        $a = $this->bootstrapper . '_Inheritance';
        $a = new $a;
        $a = $a->linearizeGraph($this->pwd, $this->cwd, $this->base);

        $b = array_slice($a[0], 0, $a[1]);

        foreach (array_reverse($b) as $c)
            if (file_exists($c .= 'bootup.patchwork.php'))
                $this->steps[] = array(null, $c);

        $b[] = $this->pwd;

        foreach ($b as $c)
            if (file_exists($c .= 'config.patchwork.php'))
                $this->steps[] = array(null, $c);

        $this->paths = $a[0];
        $this->last  = $a[1];
    }

    protected function initZcache()
    {
        // Get zcache's location

        $zc = false;

        for ($i = 0; $i <= $this->last; ++$i)
        {
            if (file_exists($this->paths[$i] . 'zcache/'))
            {
                $zc = $this->paths[$i] . 'zcache' . DIRECTORY_SEPARATOR;
                @(touch($zc . '/.patchwork.touch') && unlink($zc . '/.patchwork.touch')) || $zc = false;
                break;
            }
        }

        if (!$zc)
        {
            $zc = $this->cwd . 'zcache' . DIRECTORY_SEPARATOR;
            file_exists($zc) || mkdir($zc);
        }

        $this->zcache = $zc;
    }

    protected function exportPathData()
    {
        $this->substeps[] = array(
              "const PATCHWORK_ZCACHE=" . var_export($this->zcache, true) . ';'
            . "const PATCHWORK_PATH_LEVEL=" . var_export($this->last, true) . ';'
            . "const PATCHWORK_PROJECT_PATH=" . var_export($this->cwd, true) . ';'
            . '$patchwork_path=' . var_export($this->paths, true) . ';',
            __FILE__
        );
    }

    function error($msg, $severity = E_USER_ERROR)
    {
        $t = new Exception;
        $t = $t->getTrace();
        $e = $this->bootstrapper . '_Exception';
        return new $e($msg, 0, $severity, $t[1]['file'], $t[1]['line']);
    }

    function pushFile($file)
    {
        $this->substeps[] = array(null, dirname($this->file) . DIRECTORY_SEPARATOR . $file);
    }

    function getCurrentDir()
    {
        return dirname($this->file) . DIRECTORY_SEPARATOR;
    }

    protected function functionOverride($function, $override, $args)
    {
        ':' === substr($override, 0, 1) && $override = 'Patchwork_PHP_Override_' . substr($override, 1);
        ':' === substr($override, -1) && $override .= ':' . $function;

        if (function_exists($function))
        {
            $this->overrides[0][$function] = $override;
            $function = '__patchwork_' . $function;
        }

        $args = array($args, array(), array());

        foreach ($args[0] as $k => $v)
        {
            $args[1][] = is_string($k)
                ? $k . '=' . var_export($v, true)
                : $k = $v;

            $args[2][] = $k;
        }

        return "function {$function}(" . implode(',', $args[1]) . ") {return {$override}(" . implode(',', $args[2]) . ");}";
    }

    protected function buildEchoErrorMsg($file, $line, $what, $when)
    {
        // Try to build a nice error message about early echos

        if ($type = strlen($what))
        {
            if ('' === trim($what))
            {
                $type = $type > 1 ? "{$type} bytes of whitespace have" : 'One byte of whitespace has';
            }
            else if (0 === strncmp($what, "\xEF\xBB\xBF", 3))
            {
                $type = 'An UTF-8 byte order mark (BOM) has';
            }
            else
            {
                $type = $type > 1 ? "{$type} bytes have" : 'One byte has';
            }
        }
        else $type = 'Some bytes have';

        if ($line)
        {
            $line = " in {$file} on line {$line} or before";
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

        return "{$type} been echoed {$when}{$line}";
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
}

class Patchwork_Bootstrapper_Exception extends ErrorException {}

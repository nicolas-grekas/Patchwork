<?php

namespace ppp;

use Patchwork\PHP\Preprocessor;

spl_autoload_register('ppp\autoload');

function autoload($class)
{
    static $dir;
    isset($dir) || $dir = dirname(__DIR__) . '/class/';
    if (file_exists($class = $dir . strtr($class, '\\_', '//') . '.php')) require $class;
}

require Preprocessor::register() . dirname(__DIR__) . '/bootup.shim.php';

class ShebangPreprocessor extends Preprocessor
{
    protected static $processor;


    static function register($filter = null, $class = null)
    {
        if (empty($filter)) $filter = empty($class) ? new parent : new $class;
        $x = parent::register(new static);
        parent::register($filter);
        self::$processor = $filter;
        return $x;
    }

    function process($code)
    {
        $p = self::$processor;

        $p->uri = $this->uri;
        $p->compilerHaltOffset += strlen($code);

        if ('#!' === substr($code, 0, 2))
        {
            $r = strpos($code, "\r");
            $n = strpos($code, "\n");

            if (false === $r && false === $n) $code = '';
            else if (false === $n || ++$r === $n) $code = (string) substr($code, $r);
            else $code = (string) substr($code, $n + 1);
        }

        $p->compilerHaltOffset -= strlen($code);

        return $p->process($code);
    }
}

if (isset($_SERVER['SCRIPT_FILENAME'][0])) require ShebangPreprocessor::register() . $_SERVER['SCRIPT_FILENAME'];
else eval('?>' . file_get_contents(Preprocessor::register() . 'php://stdin'));

exit;

<?php

ppp_init();

function ppp_autoload($class)
{
    static $dir;
    isset($dir) || $dir = dirname(dirname(__FILE__)) . '/class/';
    if (file_exists($class = $dir . strtr($class, '\\_', '//') . '.php')) require $class;
}

function ppp_init()
{
    if (!defined('PHP_VERSION_ID'))
    {
        $v = array_map('intval', explode('.', PHP_VERSION, 3));

        define('PHP_VERSION_ID', 10000 * $v[0] + 100 * $v[1] + $v[2]);
        define('PHP_MAJOR_VERSION', $v[0]);
        define('PHP_MINOR_VERSION', $v[1]);
        define('PHP_RELEASE_VERSION', $v[2]);

        $v = substr(PHP_VERSION, strlen(implode('.', $v)));

        define('PHP_EXTRA_VERSION', false !== $v ? $v : '');
    }

    if (!defined('E_DEPRECATED'))
    {
        define('E_DEPRECATED', E_NOTICE);
        define('E_USER_DEPRECATED', E_USER_NOTICE);
    }

    spl_autoload_register('ppp_autoload');

    require Patchwork_PHP_Preprocessor::register() . dirname(dirname(__FILE__)) . '/bootup.shim.php';
}

class ppp_PHP_Preprocessor extends Patchwork_PHP_Preprocessor
{
    protected static $processor;


    static function register($filter = null, $class = null)
    {
        if (empty($filter)) $filter = new parent;
        $x = parent::register(new self);
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

if (isset($_SERVER['SCRIPT_FILENAME'][0])) require ppp_PHP_Preprocessor::register() . $_SERVER['SCRIPT_FILENAME'];
else eval('?>' . file_get_contents(Patchwork_PHP_Preprocessor::register() . 'php://stdin'));

exit;

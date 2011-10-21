<?php // vi: set fenc=utf-8 ts=4 sw=4 et:


// Default settings

$CONFIG += array(

    // General
    'debug.allowed'  => true,
    'debug.password' => '',
    'debug.scream'   => false, // Disable the silencing error control operator, defaults to the DEBUG_SCREAM constant if any
    'turbo'          => false, // Run patchwork at full speed, at the cost of source code desynchronisation

);


defined('DEBUG') || define('DEBUG', $CONFIG['debug.allowed'] && (!$CONFIG['debug.password'] || isset($_COOKIE['debug_password']) && $CONFIG['debug.password'] == $_COOKIE['debug_password']) ? 1 : 0);
defined('TURBO') || define('TURBO', !DEBUG && $CONFIG['turbo']);

if (TURBO)
{
    class_exists('Patchwork_Autoload', false) && Patchwork_Autoload::$turbo = true;

    spl_autoload_register('patchwork_autoload_turbo', true, true);

/**/if (function_exists('patchwork_autoload_alias'))
/**/{
        if (spl_autoload_unregister('patchwork_autoload_alias'))
            spl_autoload_register('patchwork_autoload_alias', true, true);
/**/}

    function patchwork_autoload_turbo($class)
    {
/**/    if (50300 <= PHP_VERSION_ID && PHP_VERSION_ID < 50303) // Workaround http://bugs.php.net/50731
            isset($class[0]) && '\\' === $class[0] && $class = substr($class, 1);

        if (empty($GLOBALS["c\x9D"][$a = strtolower(strtr($class, '\\', '_'))])) return;

        if (is_int($a =& $GLOBALS["c\x9D"][$a]))
        {
            $b = $a;
            unset($a);
            $a = $b - /*<*/count($GLOBALS['patchwork_path']) - PATCHWORK_PATH_LEVEL/*>*/;

            $b = strtr($class, '\\', '_');
            $i = strrpos($b, '__');
            false !== $i && isset($b[$i+2]) && '' === trim(substr($b, $i+2), '0123456789') && $b = substr($b, 0, $i);

            $a = $b . '.php.' . DEBUG . (0>$a ? -$a . '-' : $a);
        }

        $a = /*<*/PATCHWORK_PROJECT_PATH  . '.class_'/*>*/ . $a . '.zcache.php';

        $GLOBALS["a\x9D"] = false;

        if (file_exists($a)) patchwork_include_voicer($a, null);
    }
}

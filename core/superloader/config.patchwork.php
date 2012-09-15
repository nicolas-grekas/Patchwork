<?php // vi: set fenc=utf-8 ts=4 sw=4 et:

use Patchwork as p;
use Patchwork\PHP\Shim as s;

// Default settings

$CONFIG += array(
    'debug.allowed'  => true,
    'debug.password' => '',
    'turbo'          => false, // Run patchwork at full speed, at the cost of source code desynchronisation
);


defined('DEBUG') || define('DEBUG', $CONFIG['debug.allowed'] && (!$CONFIG['debug.password'] || isset($_COOKIE['debug_password']) && $CONFIG['debug.password'] == $_COOKIE['debug_password']) ? 1 : 0);

DEBUG || error_reporting(/*<*/E_ALL & ~(E_DEPRECATED | E_USER_DEPRECATED | E_STRICT)/*>*/);

if (Patchwork_Superloader::$turbo = !DEBUG && $CONFIG['turbo'])
{
    spl_autoload_register(array('Patchwork_Superloader', 'loadTurbo'), true, true);

    if (spl_autoload_unregister(array('Patchwork_Superloader', 'loadAlias')))
        spl_autoload_register(array('Patchwork_Superloader', 'loadAlias'), true, true);
}

/**/if ('\\' === DIRECTORY_SEPARATOR && !function_exists('__patchwork_file_exists'))
/**/{
        if (DEBUG)
        {
            // Replace file_exists() on Windows to check if character case is strict

            p\Shim(file_exists,   s\WinfsCase, $file);
            p\Shim(is_file,       s\WinfsCase, $file);
            p\Shim(is_dir,        s\WinfsCase, $file);
            p\Shim(is_link,       s\WinfsCase, $file);
            p\Shim(is_executable, s\WinfsCase, $file);
            p\Shim(is_readable,   s\WinfsCase, $file);
            p\Shim(is_writable,   s\WinfsCase, $file);
        }
/**/}

/**/boot::$manager->pushFile('config.setup.php');

<?php // vi: set fenc=utf-8 ts=4 sw=4 et:

use Patchwork\PHP\Override as o;

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

            Patchwork\FunctionOverride(file_exists,   o\WinfsCase, $file);
            Patchwork\FunctionOverride(is_file,       o\WinfsCase, $file);
            Patchwork\FunctionOverride(is_dir,        o\WinfsCase, $file);
            Patchwork\FunctionOverride(is_link,       o\WinfsCase, $file);
            Patchwork\FunctionOverride(is_executable, o\WinfsCase, $file);
            Patchwork\FunctionOverride(is_readable,   o\WinfsCase, $file);
            Patchwork\FunctionOverride(is_writable,   o\WinfsCase, $file);
        }
/**/}

/**/boot::$manager->pushFile('config.setup.php');

<?php // vi: set fenc=utf-8 ts=4 sw=4 et:

// Default settings

$CONFIG += array(
    'debug.allowed'  => true,
    'debug.password' => '',
    'turbo'          => false, // Run patchwork at full speed, at the cost of source code desynchronisation
);


defined('DEBUG') || define('DEBUG', $CONFIG['debug.allowed'] && (!$CONFIG['debug.password'] || isset($_COOKIE['debug_password']) && $CONFIG['debug.password'] == $_COOKIE['debug_password']) ? 1 : 0);

if (Patchwork_Superloader::$turbo = !DEBUG && $CONFIG['turbo'])
{
    spl_autoload_register(array('Patchwork_Superloader', 'loadTurbo'), true, true);

    if (spl_autoload_unregister(array('Patchwork_Superloader', 'loadAlias')))
        spl_autoload_register(array('Patchwork_Superloader', 'loadAlias'), true, true);
}

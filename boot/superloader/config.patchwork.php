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


spl_autoload_register('patchwork_autoload');

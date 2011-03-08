<?php // vi: set encoding=utf-8 expandtab shiftwidth=4:


// Basic pieces of patchwork

#patchwork pieces/mdb2
#patchwork pieces/pForm
#patchwork pieces/toolbox


// Default settings

$CONFIG += array(

    // General
    'debug.allowed'  => true,
    'debug.password' => '',
    'debug.scream'   => false, // Disable the silencing error control operator, defaults to the DEBUG_SCREAM constant if any
    'turbo'          => false, // Run patchwork at full speed, at the cost of source code desynchronisation
    'umask'          => false, // Set the user file creation mode mask

    // Patchwork
    'clientside'      => true,      // Enable browser-side page rendering when available
    'i18n.lang_list'  => '',        // List of available languages ('en|fr' for example)
    'maxage'          => 2678400,   // Max age (in seconds) for HTTP ressources caching
    'P3P'             => 'CUR ADM', // P3P - Platform for Privacy Preferences
    'xsendfile'       => false,     // "X-Sendfile" enabling pattern
    'document.domain' => '',        // Value of document.domain for clientside cross subdomain communication
    'X-UA-Compatible' => 'IE=edge,chrome=1', // X-UA-Compatible - by default use chrome frame or latest IE engine

    // Session
    'session.save_path'     => /*<*/PATCHWORK_ZCACHE/*>*/,
    'session.cookie_path'   => 'auto',
    'session.cookie_domain' => 'auto',
    'session.auth_vars'     => array(), // Set of session vars used for authentication or authorization
    'session.group_vars'    => array(), // Set of session vars whose values define user groups

    // Translation adapter
    'translator.adapter' => false,
    'translator.options' => array(),

);

defined('DEBUG') || define('DEBUG', $CONFIG['debug.allowed'] && (!$CONFIG['debug.password'] || isset($_COOKIE['debug_password']) && $CONFIG['debug.password'] == $_COOKIE['debug_password']) ? 1 : 0);
defined('TURBO') || define('TURBO', !DEBUG && $CONFIG['turbo']);

empty($CONFIG['umask']) || umask($CONFIG['umask']);
empty($CONFIG['xsendfile']) && isset($_SERVER['PATCHWORK_XSENDFILE']) && $CONFIG['xsendfile'] = $_SERVER['PATCHWORK_XSENDFILE'];

/**/if (IS_WINDOWS && !function_exists('__patchwork_file_exists'))
/**/{
        if (DEBUG)
        {
/**/        // Replace file_exists() on Windows to check if character case is strict
/**/
/**/        /*<*/patchwork_bootstrapper::alias('file_exists',   'patchwork_alias_winfs::file_exists',   array('$file'))/*>*/;
/**/        /*<*/patchwork_bootstrapper::alias('is_file',       'patchwork_alias_winfs::is_file',       array('$file'))/*>*/;
/**/        /*<*/patchwork_bootstrapper::alias('is_dir',        'patchwork_alias_winfs::is_dir',        array('$file'))/*>*/;
/**/        /*<*/patchwork_bootstrapper::alias('is_link',       'patchwork_alias_winfs::is_link',       array('$file'))/*>*/;
/**/        /*<*/patchwork_bootstrapper::alias('is_executable', 'patchwork_alias_winfs::is_executable', array('$file'))/*>*/;
/**/        /*<*/patchwork_bootstrapper::alias('is_readable',   'patchwork_alias_winfs::is_readable',   array('$file'))/*>*/;
/**/        /*<*/patchwork_bootstrapper::alias('is_writable',   'patchwork_alias_winfs::is_writable',   array('$file'))/*>*/;
        }
/**/}

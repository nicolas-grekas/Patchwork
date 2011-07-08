<?php

header('Content-type: text/plain');
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', true);

include 'DebugLog.php';

Patchwork\PHP\DebugLog::start('./output')->log(
    'debug-start',
    array(
        'start-time' => date('c'),
        'server-context' => $_SERVER,
    )
);

register_shutdown_function('log_shutdown');

echo $a; // undefined variable
eval('a()'); // non-fatal parse error
eval('a();'); // undefined function fatal error

function log_shutdown()
{
    Patchwork\PHP\DebugLog::getLogger()->log('debug-shutdown');
}

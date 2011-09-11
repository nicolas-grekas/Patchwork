<?php

use Patchwork\PHP as p;

header('Content-type: text/plain');
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', true);

include 'Walker.php';
include 'Dumper.php';
include 'JsonDumper.php';
include 'Logger.php';
include 'ErrorHandler.php';

p\ErrorHandler::start('php://stderr')->getLogger()->log(
    'debug-start',
    array(
        'start-time' => date('c'),
        'request-context' => $_SERVER,
    )
);

register_shutdown_function('log_shutdown');

user_error('user triggered warning', E_USER_WARNING);
echo $a; // undefined variable
eval('a()'); // non-fatal parse error
@eval('a();'); // silenced undefined function fatal error

function log_shutdown()
{
    p\DebugLog::getHandler()->getLogger()->log('debug-shutdown', array(
        'response-headers' => headers_list(),
    ));
}

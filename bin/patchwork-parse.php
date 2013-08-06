#!/usr/bin/env php
<?php

use Patchwork\PHP\Parser as p;

ini_set('display_errors', false);
ini_set('log_errors', true);
ini_set('error_log', 'php://stderr');
error_reporting(-1);
function_exists('xdebug_disable') and xdebug_disable();

function __autoload($class)
{
    $class = str_replace(array('\\', '_'), array('/', '/'), $class);
    require dirname(__DIR__) . '/class/' . $class . '.php';
}

class Preprocessor
{
    static function getParser($file)
    {
        $parser = new p\BracketWatcher();
        new p\ControlStructBracketer($parser);
        new p\CaseColonEnforcer($parser);
        new p\CodePathSplitterWithXDebugHacks($parser);
        new p\CodePathLoopEnlightener($parser);
        new p\CodePathElseEnlightener($parser);
        new p\CodePathSwitchEnlightener($parser);
        new p\CodePathDefaultArgsEnlightener($parser);
        new p\ShortArray($parser);
        $parser = new p\ShortOpenEcho($parser);
        $parser = new p\BinaryNumber($parser);
        $parser = new p\Backport54Tokens($parser);

        return $parser;
    }
}

$file = isset($argv[1]) ? realpath($argv[1]) : null;
$code = file_get_contents('php://stdin');

$parser = Preprocessor::getParser($file);
$code = $parser->parse($code);

if ($e = $parser->getErrors())
{
    foreach ($e as $e)
    {
        switch ($e['type'])
        {
        case 0: continue 2;
        case E_USER_NOTICE:
        case E_USER_WARNING:
        case E_USER_DEPRECATED: break;
        default:
        case E_ERROR: $e['type'] = E_USER_ERROR; break;
        case E_NOTICE: $e['type'] = E_USER_NOTICE; break;
        case E_WARNING: $e['type'] = E_USER_WARNING; break;
        case E_DEPRECATED: $e['type'] = E_USER_DEPRECATED; break;
        }

        user_error("{$e['message']} in {$file} on line {$e['line']} as parsed by {$e['parser']}", $e['type']);
    }
}

echo $code;

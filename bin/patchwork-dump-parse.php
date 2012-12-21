#!/usr/bin/env php
<?php

use Patchwork\PHP\Parser as p;

ini_set('display_errors', false);
ini_set('log_errors', true);
ini_set('error_log', 'php://stderr');
error_reporting(E_ALL | E_STRICT);
function_exists('xdebug_disable') and xdebug_disable();

function __autoload($class)
{
    $class = str_replace(array('\\', '_'), array('/', '/'), $class);
    require dirname(__DIR__) . '/class/' . $class . '.php';
}

$file = empty($argv[1])
    ? die("Please specify a PHP file as first argument\n")
    : $argv[1];

file_exists($file) || die("File not found: {$file}\n");


class Preprocessor
{
    static function getParser($file)
    {
        $parser = new p\Dumper;
        $parser = new p\ShortOpenEcho($parser);
        $parser = new p\Normalizer($parser);
        new p\BracketWatcher($parser);
        new p\CurlyDollarNormalizer($parser);
        new p\ShortArray($parser);
        $parser = new p\BinaryNumber($parser);
        $parser = new p\Backport54Tokens($parser);
        new p\Backport53Tokens($parser);
        new p\StringInfo($parser);
        new p\NamespaceInfo($parser);
        new p\ScopeInfo($parser);
        new p\ClassInfo($parser);
        new p\ConstantInliner($parser, realpath($file));
        new p\Scream($parser);

        return $parser;
    }
}

$parser = Preprocessor::getParser($file);
$code = file_get_contents($file);
$code = $parser->parse($code);

echo "\nResulting code\n==============\n";
echo $code, "\n";

if ($errors = $parser->getErrors())
{
    echo "Reported errors\n===============\n";

    foreach ($errors as $e)
    {
        echo "Line {$e['line']}: {$e['message']}\n";
    }
}

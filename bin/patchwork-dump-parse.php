#!/usr/bin/env php
<?php

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
        $parser = new Patchwork_PHP_Parser_Dumper;
        $parser = new Patchwork_PHP_Parser_ShortOpenEcho($parser);
        $parser = new Patchwork_PHP_Parser_Normalizer($parser);
        new Patchwork_PHP_Parser_BracketWatcher($parser);
        new Patchwork_PHP_Parser_CurlyDollarNormalizer($parser);
        new Patchwork_PHP_Parser_ShortArray($parser);
        $parser = new Patchwork_PHP_Parser_BinaryNumber($parser);
        $parser = new Patchwork_PHP_Parser_Backport54Tokens($parser);
        new Patchwork_PHP_Parser_Backport53Tokens($parser);
        new Patchwork_PHP_Parser_StringInfo($parser);
        new Patchwork_PHP_Parser_NamespaceInfo($parser);
        new Patchwork_PHP_Parser_ScopeInfo($parser);
        new Patchwork_PHP_Parser_ClassInfo($parser);
        new Patchwork_PHP_Parser_ConstantInliner($parser, realpath($file));
        new Patchwork_PHP_Parser_Scream($parser);

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

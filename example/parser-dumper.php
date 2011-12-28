#!/usr/bin/env php
<?php

ini_set('display_errors', 'stderr');
error_reporting(E_ALL | E_STRICT);

$file = dirname(dirname(__FILE__));

require $file . '/class/Patchwork/PHP/Parser.php';
require $file . '/class/Patchwork/PHP/Parser/Dumper.php';
require $file . '/class/Patchwork/PHP/Parser/ShortOpenEcho.php';
require $file . '/class/Patchwork/PHP/Parser/Normalizer.php';
require $file . '/class/Patchwork/PHP/Parser/BracketBalancer.php';
require $file . '/class/Patchwork/PHP/Parser/CurlyDollarNormalizer.php';
require $file . '/class/Patchwork/PHP/Parser/ShortArray.php';
require $file . '/class/Patchwork/PHP/Parser/StringInfo.php';
require $file . '/class/Patchwork/PHP/Parser/Backport54Tokens.php';
require $file . '/class/Patchwork/PHP/Parser/Backport53Tokens.php';
require $file . '/class/Patchwork/PHP/Parser/NamespaceInfo.php';
require $file . '/class/Patchwork/PHP/Parser/ScopeInfo.php';
require $file . '/class/Patchwork/PHP/Parser/ClassInfo.php';
require $file . '/class/Patchwork/PHP/Parser/ConstantInliner.php';
require $file . '/class/Patchwork/PHP/Parser/Scream.php';


$file = empty($argv[1])
    ? die("Please specify a PHP file as first argument\n")
    : $argv[1];

file_exists($file) || die("File not found: {$file}\n");


$parser = new Patchwork_PHP_Parser_Dumper;
$parser = new Patchwork_PHP_Parser_ShortOpenEcho($parser);
$parser = new Patchwork_PHP_Parser_Normalizer($parser);
new Patchwork_PHP_Parser_BracketBalancer($parser);
new Patchwork_PHP_Parser_CurlyDollarNormalizer($parser);
new Patchwork_PHP_Parser_ShortArray($parser);
new Patchwork_PHP_Parser_StringInfo($parser);
new Patchwork_PHP_Parser_Backport54Tokens($parser);
new Patchwork_PHP_Parser_Backport53Tokens($parser);
new Patchwork_PHP_Parser_NamespaceInfo($parser);
new Patchwork_PHP_Parser_ScopeInfo($parser);
new Patchwork_PHP_Parser_ClassInfo($parser);
new Patchwork_PHP_Parser_ConstantInliner($parser, realpath($file), array());
new Patchwork_PHP_Parser_Scream($parser);


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

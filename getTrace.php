<?php

// Shell script to get one agent's trace
// Setup the context needed to simulate a keys query

$_SERVER['CIA_HOME'] = $_SERVER['argv'][2];
$_SERVER['CIA_LANG'] = $_SERVER['argv'][3];
$_SERVER['CIA_REQUEST'] = $_SERVER['argv'][4];

if ($_SERVER['argv'][5]) $_SERVER['HTTPS'] = 'on';

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = implode($_SERVER['CIA_LANG'], explode('__', $_SERVER['CIA_HOME'], 2));
$_SERVER['REQUEST_URI'] = preg_replace("'^https?://[^/]*'i", '', $_SERVER['REQUEST_URI']) . '?k$=';
$_SERVER['QUERY_STRING'] = 'k$=';

$_GET = array('k$' => '');

require $_SERVER['argv'][1];

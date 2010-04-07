<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


// To generate meaningful error messages,
// this file should be parse error free even in PHP 4.0.

// To keep the global namespace clean, we use only static methods instead of any variable.
// Be aware that the use of static properties would throw a PHP 4.0 parse error.

isset($_GET['p:']) && 'exit' === $_GET['p:'] && die('Exit requested');

error_reporting(E_ALL | E_STRICT);
@ini_set('display_errors', true); // Only while bootstrapping
setlocale(LC_ALL, 'C');

if (!function_exists('version_compare') || version_compare(phpversion(), '5.1.4', '<'))
{
	die("PHP 5.1.4 or higher is required.");
}

function_exists('mb_internal_encoding') && mb_internal_encoding('UTF-8');


require dirname(__FILE__) . '/class/patchwork/bootstrapper.php';

patchwork_bootstrapper::initialize(__FILE__);


// Get lock

if (!patchwork_bootstrapper::getLock())
{
	require patchwork_bootstrapper::getCompiledFile();
	return;
}


// Parse and load common.php

eval(patchwork_bootstrapper::preprocessorPass1());
eval(patchwork_bootstrapper::preprocessorPass2());
ob_get_length() && ob_flush();


// Initialization

patchwork_bootstrapper::initInheritance();
patchwork_bootstrapper::initZcache();


// Load preconfig

while (patchwork_bootstrapper::loadConfigFile('pre'))
{
	eval(patchwork_bootstrapper::preprocessorPass1());
	eval(patchwork_bootstrapper::preprocessorPass2());
	ob_get_length() && ob_flush();
}


// Load config

patchwork_bootstrapper::initConfig();

while (patchwork_bootstrapper::loadConfigSource())
{
	eval(patchwork_bootstrapper::getConfigSource());
	ob_get_length() && ob_flush();
}


// Load postconfig

while (patchwork_bootstrapper::loadConfigFile('post'))
{
	eval(patchwork_bootstrapper::preprocessorPass1());
	eval(patchwork_bootstrapper::preprocessorPass2());
	ob_get_length() && ob_flush();
}


// Setup hook

if (!ob_get_length())
{
	class p extends patchwork {}
	patchwork_setup::hook();
}


// Save config and release lock

patchwork_bootstrapper::release();


// Let's go

patchwork::start();

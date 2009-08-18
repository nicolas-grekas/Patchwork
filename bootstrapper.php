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


isset($_GET['p:']) && 'exit' === $_GET['p:'] && die('Exit requested');

error_reporting(E_ALL | E_STRICT);
@ini_set('display_errors', true); // Only while bootstrapping


// Mandatory PHP dependencies

if (!function_exists('version_compare') || version_compare(phpversion(), '5.1.4', '<'))
{
	die("PHP 5.1.4 or higher is required.");
}
function_exists('token_get_all') || die('Patchwork Error: Extension "tokenizer" is needed and not loaded');
preg_match('/^.$/u', '§')        || die('Patchwork Error: PCRE is not compiled with UTF-8 support');
isset($_SERVER['REDIRECT_STATUS']) && '200' !== $_SERVER['REDIRECT_STATUS'] && die('Patchwork Error: initialization forbidden (try using the shortest possible URL)');

require dirname(__FILE__) . '/class/patchwork/bootstrapper.php';


// Get lock

if (!patchwork_bootstrapper::getLock(__FILE__))
{
	require patchwork_bootstrapper::getBootstrapper();
	return;
}


// Parse and load common.php

eval(patchwork_bootstrapper::preprocessorPass1());
eval(patchwork_bootstrapper::preprocessorPass2());
ob_get_length() && ob_flush();


// Initialization

patchwork_bootstrapper::initInheritance($patchwork_path);
patchwork_bootstrapper::initZcache();


// Load preconfig

while (patchwork_bootstrapper::loadConfigFile('pre'))
{
	eval(patchwork_bootstrapper::preprocessorPass1());
	eval(patchwork_bootstrapper::preprocessorPass2());
	ob_get_length() && ob_flush();
}


// Load config

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

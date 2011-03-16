<?php /***** vi: set encoding=utf-8 expandtab shiftwidth=4: ****************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
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

defined('patchwork') || define('patchwork', microtime(true));
defined('PATCHWORK_BOOTPATH') || define('PATCHWORK_BOOTPATH', '.');
@ini_set('display_errors', true);
error_reporting(E_ALL);

switch (true)
{
case file_exists((PATCHWORK_BOOTPATH ? PATCHWORK_BOOTPATH : '.') . '/.patchwork.php'):
    return require (PATCHWORK_BOOTPATH ? PATCHWORK_BOOTPATH : '.') . '/.patchwork.php';
case isset($_GET['p:']) && 'exit' === $_GET['p:']:
    die('Exit requested');
case !function_exists('version_compare') || version_compare(phpversion(), '5.1.4') < 0:
    die("PHP 5.1.4 or higher is required.");
}

setlocale(LC_ALL, 'C');
error_reporting(E_ALL | E_STRICT);

function_exists('mb_internal_encoding')
    && !in_array(strtolower(mb_internal_encoding()), array('pass', '8bit', 'utf-8'))
    && mb_internal_encoding('8bit') // if mbstring overloading is enabled
    && @ini_set('mbstring.internal_encoding', '8bit');


require dirname(__FILE__) . '/class/patchwork/bootstrapper.php';

patchwork_bootstrapper::initialize(__FILE__, PATCHWORK_BOOTPATH);


// Get lock

if (!patchwork_bootstrapper::getLock())
{
    require patchwork_bootstrapper::getCompiledFile();
    return;
}


// Parse and load common.php

eval(patchwork_bootstrapper::preprocessorPass1());
eval(patchwork_bootstrapper::preprocessorPass2());


// Initialization

patchwork_bootstrapper::initInheritance();
patchwork_bootstrapper::initZcache();


// Load preconfig

while (patchwork_bootstrapper::loadConfigFile('pre'))
{
    eval(patchwork_bootstrapper::preprocessorPass1());
    eval(patchwork_bootstrapper::preprocessorPass2());
}


// Load config

patchwork_bootstrapper::initConfig();

while (patchwork_bootstrapper::loadConfigFile(true))
{
    eval(patchwork_bootstrapper::preprocessorPass1());
    eval(patchwork_bootstrapper::preprocessorPass2());
}


// Setup hook

class_exists('patchwork', true);
patchwork_setup::hook();


// Save config and release lock

patchwork_bootstrapper::release();


// Let's go

patchwork::start();
exit;

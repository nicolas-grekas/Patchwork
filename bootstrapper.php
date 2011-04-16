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

defined('PATCHWORK_MICROTIME') || define('PATCHWORK_MICROTIME', microtime(true));
defined('PATCHWORK_BOOTPATH')  || define('PATCHWORK_BOOTPATH', '.');
@ini_set('display_errors', true);
error_reporting(E_ALL);

PATCHWORK_BOOTPATH || die('Patchwork error: PATCHWORK_BOOTPATH is empty');

if (file_exists(PATCHWORK_BOOTPATH . '/.patchwork.php'))
    return require PATCHWORK_BOOTPATH . '/.patchwork.php';

if (isset($_GET['p:']) && 'exit' === $_GET['p:'])
    die('Exit requested');

if (!function_exists('version_compare') || version_compare(phpversion(), '5.1.4') < 0)
    die("PHP 5.1.4 or higher is required");

setlocale(LC_ALL, 'C');
error_reporting(E_ALL | E_STRICT);

function_exists('mb_internal_encoding')
    && !in_array(strtolower(mb_internal_encoding()), array('pass', '8bit', 'utf-8'))
    && mb_internal_encoding('8bit') // if mbstring overloading is enabled
    && @ini_set('mbstring.internal_encoding', '8bit');

require dirname(__FILE__) . '/class/Patchwork/Bootstrapper.php';


// Initialize and get lock

if (!Patchwork_Bootstrapper::initLock(__FILE__, PATCHWORK_BOOTPATH))
{
    require Patchwork_Bootstrapper::getBootstrapper();
    return;
}


// Bootup steps evaluated in the global scope

while (Patchwork_Bootstrapper::loadNextStep())
{
    eval(Patchwork_Bootstrapper::preprocessorPass1());
    eval(Patchwork_Bootstrapper::preprocessorPass2());
}


// Cache and release lock

Patchwork_Bootstrapper::release();


// Let's go

Patchwork::start();
exit;

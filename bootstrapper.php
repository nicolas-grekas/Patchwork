<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


// To generate meaningful error messages,
// this file should be parse error free even in PHP 4.

// To keep the global namespace clean, we use only static methods instead of any variable.
// Be aware that the use of static properties would throw a PHP 4 parse error.

error_reporting(-1);
header('Content-Type: text/plain; charset=utf-8'); // Ease with early error messages
ini_set('html_errors', false);                     //  "
ini_set('display_errors', true);                   //  "
defined('PATCHWORK_BOOTPATH') or define('PATCHWORK_BOOTPATH', '.');

PATCHWORK_BOOTPATH or die('Patchwork error: PATCHWORK_BOOTPATH is empty');

if (file_exists(PATCHWORK_BOOTPATH . '/.patchwork.php'))
    return require PATCHWORK_BOOTPATH . '/.patchwork.php';

if (! function_exists('version_compare') || version_compare(phpversion(), '5.3.0') < 0)
    die("Patchwork error: PHP 5.3.0 or higher is required");

require dirname(__FILE__) . '/core/boot/class/Patchwork/Bootstrapper/Manager.php';
require dirname(__FILE__) . '/core/boot/class/Patchwork/Bootstrapper.php';

// eval() so that no parse error occurs when this file is run in PHP 5.2 and lower.

eval('class boot extends Patchwork\Bootstrapper {}');

// Bootup steps: initialize then eval in the global scope.

boot::initialize(__FILE__, PATCHWORK_BOOTPATH);
while (false !== eval('' . boot::getNextStep())) {}

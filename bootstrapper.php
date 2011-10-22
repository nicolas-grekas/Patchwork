<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
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
// this file should be parse error free even in PHP 4.

// To keep the global namespace clean, we use only static methods instead of any variable.
// Be aware that the use of static properties would throw a PHP 4 parse error.

error_reporting(E_ALL);                            // E_STRICT is not defined in PHP 4
header('Content-Type: text/plain; charset=utf-8'); // Ease with early error messages
ini_set('html_errors', false);                     //  "
ini_set('display_errors', true);                   //  "
defined('PATCHWORK_BOOTPATH') || define('PATCHWORK_BOOTPATH', '.');

PATCHWORK_BOOTPATH || die('Patchwork error: PATCHWORK_BOOTPATH is empty');

if (file_exists(PATCHWORK_BOOTPATH . '/.patchwork.php'))
    return require PATCHWORK_BOOTPATH . '/.patchwork.php';

if (!function_exists('version_compare') || version_compare(phpversion(), '5.2.0') < 0)
    die("Patchwork error: PHP 5.2.0 or higher is required");

defined('PATCHWORK_MICROTIME') || define('PATCHWORK_MICROTIME', microtime(true));
error_reporting(E_ALL | E_STRICT);
setlocale(LC_ALL, 'C');

require dirname(__FILE__) . '/boot/class/Patchwork/Bootstrapper/Manager.php';
require dirname(__FILE__) . '/boot/class/Patchwork/Bootstrapper.php';

// Bootup steps: alias, initialize then eval in the global scope
class boot extends Patchwork_Bootstrapper {}
boot::initialize(__FILE__, PATCHWORK_BOOTPATH);
while (false !== eval('' . boot::getNextStep())) {}

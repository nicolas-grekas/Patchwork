<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 3 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


// Check for required PHP version before including other
// files so people don't see scary parse errors.


// TODO: set this to patchwork's minimum PHP version

$a = '5.1.4';


function_exists('version_compare')     || die("Your PHP version is too old; $a or higher is required.");
version_compare(phpversion(), $a, '<') && die("PHP $a or higher is required.");

version_compare($a, '5.1', '<') && $a = '5.1.x';
$b = str_replace('a', 'b', array(-1 => -1));
isset($b[-1]) || die("PHP 5.0.x is buggy on your 64-bit system; you must upgrade to PHP $a
or higher. (see http://bugs.php.net/bug.php?id=34879)");


require dirname(__FILE__) . '/loader.php';

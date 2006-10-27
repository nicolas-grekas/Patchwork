<?php /*********************************************************************
 *
 *   Copyright : (C) 2006 Nicolas Grekas. All rights reserved.
 *   Email     : nicolas.grekas+patchwork@espci.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL, see COPYING
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


function mb_strlen($a) {return strlen($a);}
function mb_strpos($a, $b, $c = 0) {return strpos($a, $b, $c);}
function mb_strrpos($a, $b, $c = 0) {return strrpos($a, $b, $c);}
function mb_substr() {$a = func_get_args(); return call_user_func_array('substr', $a);}
function mb_strtolower($a) {return strtolower($a);}
function mb_strtoupper($a) {return strtoupper($a);}
function mb_substr_count($a, $b) {return substr_count($a, $b);}

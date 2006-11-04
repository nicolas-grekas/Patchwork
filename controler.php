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


$contentType = array(
	'.html' => 'text/html; charset=UTF-8',
	'.htm' => 'text/html; charset=UTF-8',
	'.css' => 'text/css; charset=UTF-8',
	'.js' => 'text/javascript; charset=UTF-8',
	'.htc' => 'text/x-component; charset=UTF-8',

	'.png' => 'image/png',
	'.gif' => 'image/gif',
	'.jpg' => 'image/jpeg',
	'.jpeg' => 'image/jpeg',

	'.pdf' => 'application/pdf',

);

$i = 0;
$len = count($GLOBALS['cia_paths']);
$lang = CIA::__LANG__() . '/';
$l_ng = '__/';

$source = false;

do
{
	$path = $GLOBALS['cia_paths'][$i++] . '/public/';

	if (file_exists($source = $path . $lang . $agent)) break;
	if (file_exists($source = $path . $l_ng . $agent)) break;
}
while (--$len);

if ($len)
{
	$mime = strtolower($mime[0]);
	$mime = isset($contentType[$mime]) ? $contentType[$mime] : false;

	if ($mime) CIA::header('Content-Type: ' . $contentType[$mime]);

	CIA::setMaxage(-1);
	CIA::writeWatchTable('public/static', 'zcache/');

	readfile($source);

	exit;
}

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


// As of PHP5.1.2, hash('md5', $str) is a lot faster than md5($str) !

function hash_algos()
{
	return array('md5', 'sha1', 'crc32');
}

function hash($algo, $data, $raw_output = false)
{
	switch ($algo)
	{
	case   'md5': return   md5($data, $raw_output);
	case  'sha1': return  sha1($data, $raw_output);
	case 'crc32': return crc32($data);
	}

	return false;
}

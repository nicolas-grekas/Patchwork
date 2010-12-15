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


class extends self
{
	function ob_handler($buffer)
	{
		parent::ob_handler($buffer);

		if ('' !== $buffer && $CONFIG['pMail.debug_email'])
		{
			$m = debug_backtrace();
			$m['_SERVER'] = $_SERVER;
			$m = serialize($m);
			$m = <<<EOTXT
pTask output
============
{$buffer}


pTask serialized backtrace
==========================
{$m}
EOTXT;

			$m = new pMail_text(
				array('To'   => $CONFIG['pMail.debug_email']),
				array('text' => $m)
			);
			$m->send();
		}

		return '';
	}
}

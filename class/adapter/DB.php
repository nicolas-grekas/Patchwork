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


class
{
	static function connect()
	{
		$db = MDB2::factory($CONFIG['DSN']);

		if (PEAR::isError($db))
		{
			trigger_error($db->getMessage(), E_USER_ERROR);
			p::disable(true);
		}


		$db->loadModule('Extended');
		$db->setErrorHandling(PEAR_ERROR_CALLBACK, 'E');
		$db->setFetchMode(MDB2_FETCHMODE_OBJECT);
		$db->setOption('default_table_type', 'InnoDB');
		$db->setOption('seqname_format', 'zeq_%s');
		$db->setOption('portability', MDB2_PORTABILITY_ALL ^ MDB2_PORTABILITY_EMPTY_TO_NULL ^ MDB2_PORTABILITY_FIX_CASE);

		$db->connect();

		$db->beginTransaction();

		$db->query('SET NAMES utf8');
		$db->query("SET collation_connection='utf8_general_ci'");

		return $db;
	}

	static function close($db)
	{
		$db->in_transaction && $db->commit();
	}
}

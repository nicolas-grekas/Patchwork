<?php

class basicData
{
	public static function getOptionType($currType = '')
	{
		return array(
			'separator' => T('Séparateur'),
			'select' => T('Liste'),
			'check' => T('Boutons radio'),
			'check-multiple' => T('Cases à cocher'),
			'quantity' => T('Quantité'),
		);

		return $types;
	}

	public static function getDic($table)
	{
		static $dic = array();

		if (!isset($dic[$table]))
		{
			$dic[$table] = array();
			$a =& $dic[$table];

			$sql = "SELECT *
					FROM $table
					WHERE position
					ORDER BY position,label";

			$result = DB()->query($sql);
			while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) $a[current($row)] = $row['label'];
		}

		return $dic[$table];
	}
}

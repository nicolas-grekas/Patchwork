<?php

class basicData
{
	public static function getOptionType()
	{
		return array(
			'separator' => T('Séparateur'),
			'select'    => T('Liste'),
			'check'     => T('Case à cocher'),
			'quantity'  => T('Quantité'),
		);
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
			while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) $a[current($row)] = T($row['label']);
		}

		return $dic[$table];
	}

	public static function yesNo()
	{
		return array(
			1 => T('Oui'),
			0 => T('Non')
		);
	}
}

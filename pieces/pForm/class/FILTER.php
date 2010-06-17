<?php

class extends self
{
	protected static function get_name(&$value, &$args)
	{
		if ($result = self::get_char($value, $args))
		{
			if (!preg_match("/\p{Lu}[^\p{Lu}\s]+$/u", $result))
			{
				$result = mb_strtolower($result);
				$result = mb_convert_case($result, MB_CASE_TITLE);
				$result = preg_replace_callback("/(\PL)(\pL)/u", array(__CLASS__, 'nameRxCallback'), $result);
			}
		}

		return $result;
	}

	protected static function nameRxCallback($m)
	{
		return $m[1] . mb_convert_case($m[2], MB_CASE_TITLE);
	}
}

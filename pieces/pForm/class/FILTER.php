<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class FILTER extends self
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

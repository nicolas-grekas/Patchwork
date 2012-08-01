<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class loop_reverse extends loop_array
{
    function __construct($loop)
    {
        $array = $this->getArray($loop, true);

        parent::__construct($array, 'filter_rawArray');
    }

    function getArray($loop, $unshift = false)
    {
        $array = array();

        while ($a = $loop->loop())
        {
            foreach ($a as &$v) if ($v instanceof loop) $v = new loop_array($this->getArray($v), 'filter_rawArray');

            $unshift ? array_unshift($array, $a) : ($array[] = $a);
        }

        return $array;
    }
}

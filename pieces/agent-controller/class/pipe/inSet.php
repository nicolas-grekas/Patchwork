<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pipe_inSet
{
    static function php($index, $set)
    {
        $set = explode(mb_substr($set, 0, 1), $set);
        return isset($set[$index + 1]) ? $set[$index + 1] : '';
    }

    static function js()
    {
        ?>/*<script>*/

function($index, $set)
{
    $set = $set.split($set.charAt(0));
    return $set[$index + 1] || '';
}

<?php   }
}

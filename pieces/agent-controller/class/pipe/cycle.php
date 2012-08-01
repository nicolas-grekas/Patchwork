<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pipe_cycle
{
    static $pool = array();

    static function php($name)
    {
        $name = (string) $name;
        $args = func_get_args();
        $key =& self::$pool[$name];

        if (is_int($key))
        {
            if (++$key >= count($args)) $key = 1;
        }
        else $key = 1;

        return (string) $args[$key];
    }

    static function js()
    {
        ?>/*<script>*/

(function()
{
    var $pool = [];

    return function($name)
    {
        $name = str($name);
        var $args = arguments;

        if (t($pool[$name]))
        {
            if (++$pool[$name] >= $args.length) $pool[$name] = 1;
        }
        else $pool[$name] = 1;

        return str($args[$pool[$name]]);
    }
})()

<?php   }
}

<?php /***** vi: set encoding=utf-8 expandtab shiftwidth=4: ****************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class pipe_echo
{
    protected static $args;

    static function php($format = '')
    {
        $args = func_get_args();
        self::$args =& $args;

        if ('' !== $format) 
        {
            $args = preg_replace_callback(
                "'(%+)([0-9]?)'",
                array(__CLASS__, 'replace_callback'),
                Patchwork::string($format)
            );
        }
        else $args = implode('', $args);

        return $args;
    }

    protected static function replace_callback($m)
    {
        if (1 === strlen($m[1]) % 2)
        {
            $m[1] = substr($m[1], 1);
            $m[2] = '' !== $m[2] ? (isset(self::$args[$m[2]+1]) ? Patchwork::string(self::$args[$m[2]+1]) : '') : '%';
        }

        return substr($m[1], 0, strlen($m[1])>>1) . $m[2];
    }

    static function js()
    {
        ?>/*<script>*/

function($format)
{
    var $i = 1, $args = arguments;
    $format = str($format);

    if ($format != '')
    {
        $i = function($m0, $m1, $m2)
        {
            if (1 == $m1.length % 2)
            {
                $m1 = $m1.substr(1);
                $m2 = '' != $m2 ? str($args[$m2-0+1]) : '%';
            }

            return $m1.substr(0, $m1.length>>1) + $m2;
        }

        $format = $format.replace(/(%+)([0-9]?)/g, $i);
    }
    else
    {
        $format = [];
        for (; $i<$args.length; ++$i) $format[$i] = $args[$i];
        $format = $format.join('');
    }

    return $format;
}

<?php   }
}

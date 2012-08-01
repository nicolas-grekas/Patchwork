<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

use patchwork\Utf8 as u;

class pipe_wordwrap
{
    static function php($string, $width = 75, $break = "\n", $cut = true)
    {
        return u::wordwrap((string) $string, (string) $width, (string) $break, $cut);
    }

    static function js()
    {
        // This JS implementation is not Grapheme Cluster aware...

        ?>/*<script>*/

function($string, $width, $break, $cut)
{
    $cut = str($cut, 1);
    $break = str($break, '\n');
    $width = str($width, 75);
    $string = str($string).split($break);

    var $i = 0, $line,
        $j, $a, $b
        $result = [];

    for (; $i < $string.length; ++$i)
    {
        $a = $string[$i].split(' ');
        $line && $result.push($line);
        $line = $a[0];

        for ($j = 1; $j < $a.length; ++$j)
        {
            $b = $a[$j];

            if ($line.length + $b.length < $width) $line += ' ' + $b;
            else
            {
                $result.push($line);
                $line = '';

                if ($cut) while ($b.length > $width)
                {
                    $result.push($b.substr(0, $width));
                    $line = $b = $b.substr($width);
                }

                if ($b) $line = $b;
            }
        }
    }

    $line && $result.push($line);

    return $result.join($break);
}

<?php   }
}

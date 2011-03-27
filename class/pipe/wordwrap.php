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

use patchwork\Utf8 as u;

class pipe_wordwrap
{
    static function php($string, $width = 75, $break = "\n", $cut = true)
    {
        return u::wordwrap(Patchwork::string($string), Patchwork::string($width), Patchwork::string($break), Patchwork::string($cut));
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

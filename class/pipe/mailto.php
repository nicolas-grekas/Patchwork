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


class pipe_mailto
{
    static function php($string, $email = '', $attributes = '')
    {
        $string = htmlspecialchars(Patchwork::string($string));
        $email  = htmlspecialchars(Patchwork::string($email));
        $email || $email = $string;
        $attributes = Patchwork::string($attributes);
        '' !== $attributes && $attributes = ' ' . $attributes;

        $email = '<a href="mailto:'
            . str_replace('@', '%5b&#97;t%5d', $email) . '"'
            . ' onmouseover="this.href=this.href.replace(\'%5bat%5d\', \'@\');' . ($email === $string ? 'this.innerHTML=this.href.substr(7);' : '') . 'this.onmouseover=null"'
            . $attributes . '>'
            . str_replace('@', '<span style="display:none">@</span>&#64;', $string)
            . '</a>';

        return $email;
    }

    static function js()
    {
        ?>/*<script>*/

function($string, $email, $attributes)
{
    $string = esc(str($string));
    $email  = esc(str($email)) || $string;
    $attributes = str($attributes);
    if ($attributes) $attributes = ' ' + $attributes;

    return '<a href="mailto:' + $email + '"' + $attributes + '>' + $string + '</a>';
}

<?php   }
}

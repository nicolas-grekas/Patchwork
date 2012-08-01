<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pipe_mailto
{
    static function php($string, $email = '', $attributes = '')
    {
        $string = htmlspecialchars($string);
        $email  = htmlspecialchars($email);
        $email || $email = $string;
        $attributes = (string) $attributes;
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

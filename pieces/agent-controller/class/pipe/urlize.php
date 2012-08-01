<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pipe_urlize
{
    const

    mailRx = '/(\s)([-a-z\d_\.\+=]+)@([-a-z\d]+(\.[-a-z\d]+)+)/i',
    httpRx = '/(\s)(http(s?):\/\/)?(((((([a-z\d]([-a-z\d]*[a-z\d])?)\.)+[a-z]{2,3})|(\d+(\.\d+){3}))(:\d+)?)(\/((([-a-z\d$_.+!*\'\[\],;:@&=?#~]|%[a-f\d]{2})+\/)*([-a-z\d$_.+!*\'\[\],;:@&=?#~]|%[a-f\d]{2})*[a-z\d$~]\/?)?)?)/i';


    static function php($string)
    {
        $string = ' ' . $string;

        $string = preg_replace(
            self::mailRx . 'u',
            '$1<a href="mailto:$2[&#97;t]$3">$2<span style="display:none">@</span>&#64;$3</a>',
            $string
        );

        $string = preg_replace(
            self::httpRx . 'u',
            '$1<a href="http$3://$4">$4</a>',
            $string
        );

        return substr($string, 1);
    }

    static function js()
    {
        ?>/*<script>*/

function($string)
{
    return (' '+$string).replace(
        <?php echo self::mailRx?>g, '$1<a href="mailto:$2@$3">$2@$3</a>'
    ).replace(
        <?php echo self::httpRx?>g, '$1<a href="http$3://$4">$4</a>'
    ).substr(1);
}

<?php   }
}

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


class pipe_urlize
{
    const

    mailRx = '/(\s)([-a-z\d_\.\+=]+)@([-a-z\d]+(\.[-a-z\d]+)+)/i',
    httpRx = '/(\s)(http(s?):\/\/)?(((((([a-z\d]([-a-z\d]*[a-z\d])?)\.)+[a-z]{2,3})|(\d+(\.\d+){3}))(:\d+)?)(\/((([-a-z\d$_.+!*\'\[\],;:@&=?#~]|%[a-f\d]{2})+\/)*([-a-z\d$_.+!*\'\[\],;:@&=?#~]|%[a-f\d]{2})*[a-z\d$~]\/?)?)?)/i';


    static function php($string)
    {
        $string = ' ' . Patchwork::string($string);

        $string = preg_replace(
            self::mailRx . 'u',
            '$1<a href="mailto:$2[&#97;t]$3"><span style="display:none">@</span>&#64;$3</a>',
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

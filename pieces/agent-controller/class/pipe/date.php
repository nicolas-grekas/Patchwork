<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pipe_date
{
    static function php($time, $format = false)
    {
        if (false === $format)
        {
            $format = (string) $time;
            $time = $_SERVER['REQUEST_TIME'];
        }
        else
        {
            $time = (string) $time;
            $format = (string) $format;
        }

        return date($format, $time);
    }

    static function js()
    {
        ?>/*<script>*/

function php_date($time, $format)
{
    if (t($format))
    {
        $format = str($format);
        $time = t($time) ? +$time||0 : null;
    }
    else
    {
        $format = str($time);
        $time = new Date/1000;
    }

    $time = new Date(1000*$time);

    var $result = [],
        $a = /(\d\d):(\d\d):(\d\d)/,
        $GMT = $time.toGMTString().match($a),
        $local = (''+$time).match($a),
        $zone = (''+$time).match(/([A-Z]{3})?([-\+]\d{2})(\d{2})/),

        // This could easily be translated, but shouldn't
        // the format itself vary with the language ?
        // Also, native PHP's date() speaks only english.
        $month = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        $day = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],

        $i = 0,
        $len = $format.length,
        $t,

        $token = {
            a: function()
            {
                // Lowercase Ante meridiem and Post meridiem am or pm
                return $local[1]<12 ? 'am' : 'pm';
            },

            A: function()
            {
                // Uppercase Ante meridiem and Post meridiem AM or PM
                return $local[1]<12 ? 'AM' : 'PM';
            },

            B: function()
            {
                // Internet Swatch Time 000 to 999
                return (''+parseInt(((60*(60*(+$GMT[1]+1) + +$GMT[2]) + +$GMT[3])%86400)/86.4+1000)).substr(1);
            },

            c: function()
            {
                // ISO 8601 date (Ex. 2004-02-12T15:19:21+00:00)
                return php_date($time/1000, 'Y-m-d\\TH:i:s') + $zone[2] + ':' + $zone[3];
            },

            d: function()
            {
                // Day of the month, 2 digits with leading zeros 01 to 31
                return $local[4] < 10 ? '0' + +$local[4] : $local[4];
            },

            D: function()
            {
                // A textual representation of a day, three letters Mon through Sun
                return $day[$time.getDay()].substr(0, 3);
            },

            F: function()
            {
                // A full textual representation of a month, such as January or March January through December
                return $month[$time.getMonth()];
            },

            g: function()
            {
                // 12-hour format of an hour without leading zeros 1 through 12
                return $local[1]%12;
            },

            G: function()
            {
                // 24-hour format of an hour without leading zeros 0 through 23
                return +$local[1];
            },

            h: function()
            {
                // 12-hour format of an hour with leading zeros 01 through 12
                return (''+($local[1]%12 + 100)).substr(1);
            },

            H: function()
            {
                // 24-hour format of an hour with leading zeros 00 through 23
                return $local[1];
            },

            i: function()
            {
                // Minutes with leading zeros 00 to 59
                return $local[2];
            },

            I: function()
            {
                // Whether or not the date is in daylights savings time 1 if Daylight Savings Time, 0 otherwise.
                return $time.getTimezoneOffset() == -60 * $zone[2] + +$zone[3] ? 0 : 1;
            },

            j: function()
            {
                // Day of the month without leading zeros 1 to 31
                return +$local[4];
            },

            l: function()
            {
                // A full textual representation of the day of the week Sunday through Saturday
                return $day[$time.getDay()];
            },

            L: function()
            {
                // Whether it's a leap year 1 if it is a leap year, 0 otherwise.
                return $local[5]%4 ? 0 : 1;
            },

            m: function()
            {
                // Numeric representation of a month, with leading zeros 01 through 12
                return (''+($time.getMonth() + 101)).substr(1);
            },

            M: function()
            {
                // A short textual representation of a month, three letters Jan through Dec
                return $month[$time.getMonth()].substr(0, 3);
            },

            n: function()
            {
                // Numeric representation of a month, without leading zeros 1 through 12
                return $time.getMonth() + 1;
            },

            O: function()
            {
                // Difference to Greenwich time (GMT) in hours Example: +0200
                return $zone[2] + $zone[3];
            },

            r: function()
            {
                // RFC 2822 formatted date Example: Thu, 21 Dec 2000 16:01:07 +0200
                return php_date($time/1000, 'D, d M Y H:i:s O');
            },

            s: function()
            {
                // Seconds, with leading zeros 00 through 59
                return $local[3];
            },

            S: function()
            {
                // English ordinal suffix for the day of the month, 2 characters st, nd, rd or th. Works well with j
                $a = $local[4];
                return 4<=$a && $a<=20 ? 'th' : (['st', 'nd', 'rd'][$a%10-1] || 'th');
            },

            t: function()
            {
                // Number of days in the given month 28 through 31
                return new Date($local[5], $time.getMonth()+1, 0).getDate();
            },

            T: function()
            {
                // Timezone setting of this machine Examples: EST, MDT ...
                return $zone[0];
            },

            U: function()
            {
                // Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT)
                return parseInt($time/1000);
            },

            w: function()
            {
                // Numeric representation of the day of the week 0 (for Sunday) through 6 (for Saturday)
                return $time.getDay();
            },

            W: function()
            {
                // ISO-8601 week number of year, weeks starting on Monday Example: 42 (the 42nd week in the year)
                $a = new Date($local[5], 0, 1, 0, 0, 0, 0).getDay() - 1;
                if ($a < 0) $a = 6;
                return Math.ceil((Date.UTC($local[5], $time.getMonth(), $local[4]) - Date.UTC($local[5], 0, 8 - $a)) / (1000 * 60 * 60 * 24 * 7)) + 1;
            },

            y: function()
            {
                // A two digit representation of a year Examples: 99 or 03
                return $local[5].substr(2);
            },

            Y: function()
            {
                // A full numeric representation of a year, 4 digits Examples: 1999 or 2003
                return $local[5];
            },

            z: function()
            {
                // The day of the year (starting from 0) 0 through 365
                return (Date.UTC($local[5]/1, $time.getMonth(), $local[4]/1) - Date.UTC($local[5], 0, 1)) / (1000 * 60 * 60 * 24);
            },

            Z: function()
            {
                // Timezone offset in seconds. The offset for timezones west of UTC is always negative, and for those east of UTC is always positive. -43200 through 43200
                return 60 * ( 60*$zone[2] + $zone[3]/1 );
            }
        };

    $local[4] = $time.getDate();
    $local[5] = (''+$time).match(/ (\d\d\d\d)/)[1];

    while ($i < $len)
    {
        $t = $format.charAt($i);
        $result.push($token[$t] ? $token[$t]() : ('\\' == $t && $i+1 < $len ? $format.charAt(++$i) : $t));
        ++$i;
    }

    return $result.join('');
}

<?php   }
}

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


class patchwork_httpRange
{
    static function negociate($filesize, $ETag, $LastModified)
    {
        if (!isset($_SERVER['HTTP_RANGE'])) return false;

        $range = str_replace(' ', '', $_SERVER['HTTP_RANGE']);
        if (!preg_match('/^bytes=(?:\d+-\d*|-\d+)(?:,(?:\d+-\d*|-\d+))*$/', $range)) return false;


        $if_range = isset($_SERVER['HTTP_IF_RANGE']);

        if ($if_range
            && $_SERVER['HTTP_IF_RANGE'] != $ETag
            && $_SERVER['HTTP_IF_RANGE'] != $LastModified
        ) return false;

        if (isset($_SERVER['HTTP_UNLESS_MODIFIED_SINCE'])
            && !isset($_SERVER['HTTP_IF_UNMODIFIED_SINCE'])
        ) $_SERVER['HTTP_IF_UNMODIFIED_SINCE'] = $_SERVER['HTTP_UNLESS_MODIFIED_SINCE'];

        if (isset($_SERVER['HTTP_IF_UNMODIFIED_SINCE']))
        {
            $r = explode(';', $_SERVER['HTTP_IF_UNMODIFIED_SINCE'], 2);
            if (strtotime($r[0]) != $LastModified) $_SERVER['HTTP_IF_MATCH'] = '';
        }

        if (isset($_SERVER['HTTP_IF_MATCH']) && $_SERVER['HTTP_IF_MATCH'] != $ETag)
        {
            return $if_range
                ? false
                : array(
                    array('HTTP/1.1 412 Precondition Failed'),
                    array()
                );
        }


        $r = explode(',', substr($range, 6));

        $range = array();
        --$filesize;

        foreach ($r as $r)
        {
            list($min, $max) = explode('-', $r);

            if ('' === $min)
            {
                $max = (int) $max;
                if ($max) $range[] = array($max < $filesize ? $filesize - $max + 1 : $filesize, $filesize);
            }
            else if ('' === $max)
            {
                $min = (int) $min;
                if ($min <= $filesize) $range[] = array($min, $filesize);
            }
            else if ($min > $max) return false;
            else
            {
                $min = (int) $min;

                if ($min <= $filesize)
                {
                    $max = (int) $max;
                    $range[] = array($min, $max > $filesize ? $filesize : $max);
                }
            }
        }

        if (!$range)
        {
            return $if_range
                ? false
                : array(
                    array(
                        'HTTP/1.1 416 Requested Range Not Satisfiable',
                        'Content-Range: */' . ($filesize+1)
                    ),
                    array()
                );
        }

        return array(
            array('HTTP/1.1 206 Partial content'),
            $range
        );
    }


    protected static $stringBuffer;

    static function sendChunks(&$range, &$h, $mime, $size)
    {
        array_map('header', $range[0]);

        $range =& $range[1];

        if (is_string($h))
        {
            header('Content-Length: 0');

            $range || $h = '';
            $size = strlen($h);
            self::$stringBuffer = array('');
        }
        else self::$stringBuffer = false;

        if (!$range) return;


        if (1 == count($range))
        {
            list($min, $max) = $range[0];

            header('Content-Type: ' . $mime);
            header('Content-Length: ' . ($max - $min + 1));
            header("Content-Range: bytes {$min}-{$max}/{$size}");

            self::sendChunk($h, $min, $max);
        }
        else
        {
            $boundary = substr(md5(mt_rand()), -16);
            $len = strlen($boundary);
            $lenOffset = 49 + $len + strlen($mime) + strlen((string) $size);
            $len += 8;

            foreach ($range as $r) $len += $lenOffset + $r[1] - $r[0] + 1 + strlen(implode('', $r));

            header('Content-Length: ' . $len);
            header('Content-Type: multipart/byteranges; boundary=' . $boundary);

            foreach ($range as $r)
            {
                list($min, $max) = $r;

                $r = $boundary;
                $r = "\r\n--{$r}\r\n"
                    . "Content-Type: {$mime}\r\n"
                    . "Content-Range: bytes {$min}-{$max}/{$size}\r\n\r\n";

                if (self::$stringBuffer) self::$stringBuffer[] = $r;
                else echo $r;

                self::sendChunk($h, $min, $max);
            }

            $r = $boundary;
            $r = "\r\n--{$r}--\r\n";
            if (self::$stringBuffer) self::$stringBuffer[] = $r;
            else echo $r;
        }

        self::$stringBuffer && $h = implode('', self::$stringBuffer);
    }

    protected static function sendChunk(&$h, $min, $max)
    {
        $max -= $min - 1;

        if (self::$stringBuffer) self::$stringBuffer[] = substr($h, $min, $max);
        else
        {
            fseek($h, $min);

            while ($max >= 8192)
            {
                echo fread($h, 8192);
                $max -= 8192;
            }

            if ($max) echo fread($h, $max);
        }
    }
}

<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class zipStream
{
    const

    LEVEL = -1,
    contentType = 'application/zip';


    public $level;

    protected $cdr = array(), $dataLen = 0;


    function __construct($name = '', $level = self::LEVEL)
    {
        $this->level = $level;

        if ($name)
        {
            header('Content-Type: ' . self::contentType);

            $name = Patchwork\Utf8::toAscii($name);
            $name = str_replace('"', "''", $name);

            header('Content-Disposition: attachment; filename="' . $name . '.zip"');
        }
    }

    function __destruct()
    {
        $this->dataLen && $this->close();
    }


    function streamData($data, $name, $time = 0, $level = -1)
    {
        $time = $this->dosTime($time);

        $crc  = crc32($data);
        $dlen = strlen($data);

        $level < 0 && $level = (int) $this->level;
        $level < 0 && $level = self::LEVEL;

        $data = gzdeflate($data, $level);
        $zlen = strlen($data);

        $name = strtr($name, '\\', '/');
        $n = @iconv('UTF-8', 'CP850', $name);

        // If CP850 can not represent the filename, use unicode
        if ($name !== @iconv('CP850', 'UTF-8', $n))
        {
            $n = $name;
            $h = "\x00\x08";
        }
        else $h = "\x00\x00";

        $nlen = strlen($n);

        $h =  "\x14\x00"       // version needed to extract
            . $h               // general purpose bit flag
            . "\x08\x00"       // compression method
            . pack('V', $time) // mtime
            . pack('V', $crc)  // crc32
            . pack('V', $zlen) // compressed size
            . pack('V', $dlen) // uncompressed size
            . pack('v', $nlen) // length of filename
            . pack('v', 0);    // extra field length

        echo "\x50\x4B\x03\x04", $h, $n, $data;

        $dlen = $this->dataLen;
        $this->dataLen += 4 + strlen($h) + $nlen + $zlen;

        $this->cdr[] = "\x50\x4B\x01\x02"
            . "\x00\x00"       // version made by
            . $h
            . pack('v', 0)     // comment length
            . pack('v', 0)     // disk number start
            . pack('v', 0)     // internal file attributes
            . pack('V', 32)    // external file attributes - "archive" bit set
            . pack('V', $dlen) // relative offset of local header
            . $n;
    }

    function streamFile($file, $name = '', $level = -1)
    {
        $name || $name = basename($file);

        $this->streamData(
            file_get_contents($file),
            $name,
            filemtime($file),
            $level
        );
    }

    function close()
    {
        if (!$this->dataLen) return;

        $cdrCount = count($this->cdr);
        $cdrLen = 0;

        for ($i = 0; $i < $cdrCount; ++$i)
        {
            $cdrLen += strlen($this->cdr[$i]);
            echo $this->cdr[$i];
            unset($this->cdr[$i]);
        }

        echo "\x50\x4B\x05\x06\x00\x00\x00\x00",
            pack('v', $cdrCount),      // total # of entries "on this disk"
            pack('v', $cdrCount),      // total # of entries overall
            pack('V', $cdrLen),        // size of central dir
            pack('V', $this->dataLen), // offset to start of central dir
            "\x00\x00";                // general file comment length

        $this->dataLen = 0;
    }

    protected function dosTime($time)
    {
        $time || $time = $_SERVER['REQUEST_TIME'];

        $time = getdate($time);

        if ($time['year'] < 1980) return 0x210000;

        $time['year'] -= 1980;

        return ($time['year']    << 25)
             | ($time['mon']     << 21)
             | ($time['mday']    << 16)
             | ($time['hours']   << 11)
             | ($time['minutes'] <<  5)
             | ($time['seconds'] >>  1);
    }
}

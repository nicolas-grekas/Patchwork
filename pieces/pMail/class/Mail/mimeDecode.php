<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class Mail_mimeDecode extends self
{
    function _decodeHeader($input)
    {
        $input = iconv_mime_decode($input);
        $input = FILTER::get($input, 'text');

        return $input;
    }

    function _decode($headers, $body, $default_ctype = 'text/plain')
    {
        $return = parent::_decode($headers, $body, $default_ctype);

        if (isset($return->body))
        {
            $charset = empty($return->ctype_parameters['charset']) ? false : strtolower(trim($return->ctype_parameters['charset']));
            $ctype = strtolower(isset($return->ctype_primary) ? $return->ctype_primary . '/' . $return->ctype_secondary : $default_ctype);

            if (!$charset) switch ($ctype)
            {
            default: return $return;

            case 'text/html':
            case 'text/plain':
                $charset = @iconv('UTF-8', 'UTF-8', $return->body) === $return->body ? 'utf-8' : 'windows-1252';
            }

            if ('iso-8859-1' === $charset) $charset = 'windows-1252';

            $charset = @iconv($charset, 'UTF-8//IGNORE', $return->body);
            false === $charset && $charset = utf8_encode($return->body);

            $return->body = FILTER::get($charset, 'text');
        }

        return $return;
    }
}

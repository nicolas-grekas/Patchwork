<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2012 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


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

            switch ($charset)
            {
            case 'iso-8859-1':
                $charset = utf8_encode($return->body);
                break;

            case 'windows-1252':
                if (function_exists('patchwork_utf8_encode'))
                {
                    $charset = patchwork_utf8_encode($return->body);
                    break;
                }

            default:
                $charset = @iconv($charset, 'UTF-8//IGNORE', $return->body);
                false === $charset && $charset = utf8_encode($return->body);
            }

            $return->body = FILTER::get($charset, 'text');
        }

        return $return;
    }
}

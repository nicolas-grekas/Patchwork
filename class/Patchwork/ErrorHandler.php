<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
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

namespace Patchwork;

class ErrorHandler extends PHP\DebugLog
{
    function logError($code, $message, $file, $line, $context, $trace_offset = 1)
    {
        if (error_reporting())
        {
            switch ($code)
            {
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
/**/            if (!DEBUG)
                    return;
            case E_NOTICE:
            case E_STRICT:
                if (strpos($message, '__00::')) return;
                if ('-' === substr($file, -12, 1)) return;
                break;

            case E_WARNING:
                if (stripos($message, 'safe mode')) return;
            }

            \Patchwork::setMaxage(0);
            \Patchwork::setExpires('onmaxage');
            $GLOBALS['patchwork_private'] = true;

            parent::logError($code, $message, $file, $line, $context, $trace_offset);

            switch ($code)
            {
            case E_ERROR:
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
                exit;
            }
        }
        else return false;
    }
}

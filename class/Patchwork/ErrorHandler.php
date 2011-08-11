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

class_exists('Patchwork\PHP\DebugLog') || __autoload('Patchwork\PHP\DebugLog'); // http://bugs.php.net/42098 workaround

class ErrorHandler extends PHP\DebugLog
{
    public $lock = false;

    function logError($code, $message, $file, $line, $context, $trace_offset = 0, $log_time = 0)
    {
        if (error_reporting() & $code)
        {
            $log_time || $log_time = microtime(true);
            0 <= $trace_offset && ++$trace_offset;

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

            parent::logError($code, $message, $file, $line, $context, $trace_offset, $log_time);

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

    function logLastError($code, $message, $file, $line)
    {
        // Do not duplicate the last error
    }
}

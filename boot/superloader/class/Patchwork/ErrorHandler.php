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

// http://bugs.php.net/42098 workaround
class_exists('Patchwork\PHP\ErrorHandler') || __autoload('Patchwork\PHP\ErrorHandler');

class ErrorHandler extends PHP\ErrorHandler
{
    public $scream = /*<*/DEBUG ? -1 : 0/*>*/;

    function handleError($type, $message, $file, $line, $scope, $trace_offset = 0, $log_time = 0)
    {
        // Silence strict and deprecated notices for classes and files coming from include_path
        if (/*<*/(E_NOTICE | E_STRICT | E_DEPRECATED | E_USER_DEPRECATED)/*>*/ & $type)
            if (strpos($message, '__00::') || '-' === substr($file, -12, 1))
                $e = error_reporting(81);

        0 <= $trace_offset && ++$trace_offset;
        parent::handleError($type, $message, $file, $line, $scope, $trace_offset, $log_time);
        isset($e) && error_reporting($e);

        return (bool) (error_reporting() & $type);
    }

    function getLogger()
    {
        if (isset($this->logger)) return $this->logger;
        isset(self::$logStream) || self::$logStream = fopen(self::$logFile, 'ab');

        // http://bugs.php.net/42098 workaround
        class_exists('Patchwork\Logger') || __autoload('Patchwork\Logger');
        $l = new Logger(self::$logStream);
        $l->lock = false;
        $l->lineFormat = sprintf('%010d', substr(mt_rand(), -10)) . ": %s\n";

        return $this->logger = $l;
    }
}

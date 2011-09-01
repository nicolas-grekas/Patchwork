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
class_exists('Patchwork\PHP\Logger') || __autoload('Patchwork\PHP\Logger');

class Logger extends PHP\Logger
{
    protected $firstEvent = true;

    function writeEvent($type, $data)
    {
        if ($this->firstEvent)
        {
            $this->firstEvent = false;

            // http://bugs.php.net/42098 workaround
            class_exists('Patchwork\PHP\Dumper') || __autoload('Patchwork\PHP\Dumper');

            $data['patchwork'] = array(
                'app' => PATCHWORK_PROJECT_PATH,
                'i18n' => PATCHWORK_I18N,
                'debug' => DEBUG,
                'turbo' => TURBO,
                'utime' => PATCHWORK_MICROTIME,
                'stime' => $this->startTime,
                'level' => PATCHWORK_PATH_LEVEL,
                'zcache' => PATCHWORK_ZCACHE,
                'paths' => $GLOBALS['patchwork_path'],
            );
            $data['_SERVER'] = $_SERVER;
        }

        return parent::writeEvent($type, $data);
    }
}

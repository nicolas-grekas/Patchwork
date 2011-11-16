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
    public $writeLock = false;

    function writeEvent($type, $data)
    {
        if ('php-error' === $type || 'php-exception' === $type)
        {
            \Patchwork::setMaxage(0);
            \Patchwork::setExpires('onmaxage');
            $GLOBALS['patchwork_private'] = true;
        }

        if ($this->isFirstEvent)
        {
            // http://bugs.php.net/42098 workaround
            class_exists('Patchwork\PHP\Walker') || __autoload('Patchwork\PHP\Walker');
            class_exists('Patchwork\PHP\Dumper') || __autoload('Patchwork\PHP\Dumper');
            class_exists('Patchwork\PHP\JsonDumper') || __autoload('Patchwork\PHP\JsonDumper');

            $data['patchwork'] = array(
                'i18n' => PATCHWORK_I18N,
                'debug' => DEBUG,
                'turbo' => Superloader::$turbo,
                'level' => PATCHWORK_PATH_LEVEL,
                'zcache' => PATCHWORK_ZCACHE,
                'paths' => $GLOBALS['patchwork_path'],
            );
        }

        return parent::writeEvent($type, $data);
    }
}

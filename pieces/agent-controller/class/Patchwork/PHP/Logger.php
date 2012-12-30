<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork\PHP;

class Logger extends self
{
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
            $data['patchwork'] = array(
                'i18n' => PATCHWORK_I18N,
                'debug' => DEBUG,
                'turbo' => \Patchwork\Superloader::$turbo,
                'level' => PATCHWORK_PATH_LEVEL,
                'zcache' => PATCHWORK_ZCACHE,
                'paths' => $GLOBALS['patchwork_path'],
            );
        }

        return parent::writeEvent($type, $data);
    }
}

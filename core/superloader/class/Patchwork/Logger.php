<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork;

// http://bugs.php.net/42098 workaround
class_exists('Patchwork\PHP\Logger') || eval(';') || __autoload('Patchwork\PHP\Logger');

class Logger extends PHP\Logger
{
    public $writeLock = false;

    function writeEvent($type, $data)
    {
        if ($this->isFirstEvent)
        {
            // http://bugs.php.net/42098 workaround
            class_exists('Patchwork\PHP\Walker') || eval(';') || __autoload('Patchwork\PHP\Walker');
            class_exists('Patchwork\PHP\Dumper') || eval(';') || __autoload('Patchwork\PHP\Dumper');
            class_exists('Patchwork\PHP\Shim\Xml') || eval(';') || __autoload('Patchwork\PHP\Shim\Xml');
            class_exists('Patchwork\PHP\JsonDumper') || eval(';') || __autoload('Patchwork\PHP\JsonDumper');
        }

        return parent::writeEvent($type, $data);
    }
}

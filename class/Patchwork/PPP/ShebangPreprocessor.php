<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2013 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork\PPP;

class ShebangPreprocessor extends Preprocessor
{
    protected static $processor;


    static function register($filter = null, $class = null)
    {
        if (empty($filter)) $filter = empty($class) ? new parent : new $class;
        $x = parent::register(new static);
        parent::register($filter);
        self::$processor = $filter;
        return $x;
    }

    function process($code)
    {
        $p = self::$processor;

        $p->uri = $this->uri;
        $p->compilerHaltOffset += strlen($code);

        if ('#!' === substr($code, 0, 2))
        {
            $r = strpos($code, "\r");
            $n = strpos($code, "\n");

            if (false === $r && false === $n) $code = '';
            else if (false === $n || ++$r === $n) $code = (string) substr($code, $r);
            else $code = (string) substr($code, $n + 1);
        }

        $p->compilerHaltOffset -= strlen($code);

        return $p->process($code);
    }
}

<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2013 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork\PHP\Parser;

use Patchwork\PHP\Parser;

/**
 * The BackportTokens parser helps in backporting tokens introduced by later PHP versions.
 */
class BackportTokens extends Parser
{
    protected $backports = array();

    function __construct(parent $parent)
    {
        $b = $this->backports;

        foreach ($b as $k => $i)
            if (self::T_OFFSET >= $i)
                unset($b[$k]);

        parent::__construct($parent);

        $this->backports += $b;
    }

    protected function getTokens($code, $is_fragment)
    {
        $b = $this->backports;

        foreach ($b as $k => $i)
            if (false === stripos($code, $k))
                unset($b[$k]);

        $code = parent::getTokens($code, $is_fragment);
        $i = 0;

        if ($b)
            while (isset($code[++$i]))
                if (T_STRING === $code[$i][0] && isset($b[$k = strtolower($code[$i][1])]))
                    $code[$i][0] = $b[$k];

        return $code;
    }
}

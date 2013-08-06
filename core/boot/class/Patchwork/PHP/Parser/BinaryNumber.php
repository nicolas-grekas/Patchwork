<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork\PHP\Parser;

use Patchwork\PHP\Parser;

/**
 * The BinaryNumber parser backports binary number notation introduced in PHP 5.4.
 */
class BinaryNumber extends Parser
{
    protected function getTokens($code, $is_fragment)
    {
        if ($this->targetPhpVersionId < 50400 && stripos($code, '0b') && preg_match("'0b[B][01]'", $code))
        {
            $this->unregister(array('catch0b' => T_LNUMBER));
            $this->register(array('catch0b' => T_LNUMBER));
        }

        return parent::getTokens($code, $is_fragment);
    }

    protected function catch0b(&$token)
    {
        if (PHP_VERSION_ID >= 50400)
        {
            if (0 === stripos($token[1], '0b'))
            {
                $token[1] = sprintf('0x%X', bindec(substr($token[1], 2)));
            }
        }
        else if ('0' === $token[1] && $t =& $this->tokens)
        {
            $m = $t[$this->index];

            if (T_STRING === $m[0] && preg_match("'^[bB]([01]+)(.*)'", $m[1], $m))
            {
                $token[1] = sprintf('0x%X', bindec($m[1]));

                if (empty($m[2])) unset($t[$this->index++]);
                else $t[$this->index][1] = $m[2];
            }
        }
    }
}

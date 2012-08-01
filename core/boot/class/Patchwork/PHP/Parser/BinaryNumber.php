<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

/**
 * The BinaryNumber parser backports binary number notation introduced in PHP 5.4.
 */
class Patchwork_PHP_Parser_BinaryNumber extends Patchwork_PHP_Parser
{
    protected function getTokens($code)
    {
        if (PHP_VERSION_ID < 50400 && false !== $i = stripos($code, '0b'))
            if ('0b' === strtolower(rtrim(substr($code, $i, 3), '01')))
                $this->register(array('catch0b' => T_LNUMBER));

        return parent::getTokens($code);
    }

    protected function catch0b(&$token)
    {
        if ('0' === $token[1] && $t =& $this->tokens)
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

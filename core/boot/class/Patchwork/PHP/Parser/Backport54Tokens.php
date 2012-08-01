<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

defined('T_TRAIT') || Patchwork_PHP_Parser::createToken('T_TRAIT');
defined('T_TRAIT_C') || Patchwork_PHP_Parser::createToken('T_TRAIT_C');
defined('T_CALLABLE') || Patchwork_PHP_Parser::createToken('T_CALLABLE');
defined('T_INSTEADOF') || Patchwork_PHP_Parser::createToken('T_INSTEADOF');

/**
 * The Backport54Tokens parser backports tokens introduced in PHP 5.4.
 */
class Patchwork_PHP_Parser_Backport54Tokens extends Patchwork_PHP_Parser
{
    protected $backports = array(
        'trait' => T_TRAIT,
        'callable' => T_CALLABLE,
        '__trait__' => T_TRAIT_C,
        'insteadof' => T_INSTEADOF,
    );

    protected function getTokens($code)
    {
        foreach ($this->backports as $k => $i)
            if (self::T_OFFSET >= $i || false === stripos($code, $k))
                unset($this->backports[$k]);

        $code = parent::getTokens($code);
        $i = 0;

        if ($this->backports)
            while (isset($code[++$i]))
                if (T_STRING === $code[$i][0] && isset($this->backports[strtolower($code[$i][1])]))
                    $code[$i][0] = $this->backports[strtolower($code[$i][1])];

        return $code;
    }
}

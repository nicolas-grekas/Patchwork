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

defined('T_TRAIT') || Parser::createToken('T_TRAIT');
defined('T_TRAIT_C') || Parser::createToken('T_TRAIT_C');
defined('T_CALLABLE') || Parser::createToken('T_CALLABLE');
defined('T_INSTEADOF') || Parser::createToken('T_INSTEADOF');

/**
 * The Backport54Tokens parser backports tokens introduced since PHP 5.4.
 */
class Backport54Tokens extends BackportTokens
{
    protected

    $dependencies = array('Backport55Tokens' => 'backports'),
    $backports = array(
        'trait' => T_TRAIT,
        'callable' => T_CALLABLE,
        '__trait__' => T_TRAIT_C,
        'insteadof' => T_INSTEADOF,
    );
}

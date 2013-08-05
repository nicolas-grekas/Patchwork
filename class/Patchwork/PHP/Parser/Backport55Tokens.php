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

defined('T_YIELD') || Parser::createToken('T_YIELD');
defined('T_FINALLY') || Parser::createToken('T_FINALLY');

/**
 * The Backport55Tokens parser backports tokens introduced since PHP 5.5.
 *
 * @todo Work around https://bugs.php.net/60097
 * @todo Backport `self` and `parent` case insensitivity
 */
class Backport55Tokens extends BackportTokens
{
    protected

    $backports = array(
        'yield' => T_YIELD,
        'finally' => T_FINALLY,
    );
}

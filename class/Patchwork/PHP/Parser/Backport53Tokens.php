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

defined('T_DIR') || Parser::createToken('T_DIR');
defined('T_GOTO') || Parser::createToken('T_GOTO');
defined('T_NS_C') || Parser::createToken('T_NS_C');
defined('T_NAMESPACE') || Parser::createToken('T_NAMESPACE');

/**
 * The Backport53Tokens parser backports tokens introduced since PHP 5.3.
 *
 * @todo Backport nowdoc syntax, allow heredoc in static declarations.
 */
class Backport53Tokens extends BackportTokens
{
    protected

    $dependencies = array('Backport54Tokens' => 'backports'),
    $backports = array(
        'goto' => T_GOTO,
        '__dir__' => T_DIR,
        'namespace' => T_NAMESPACE,
        '__namespace__' => T_NS_C,
    );
}

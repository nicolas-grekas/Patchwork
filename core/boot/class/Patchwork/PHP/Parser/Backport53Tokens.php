<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

defined('T_DIR') || Patchwork_PHP_Parser::createToken('T_DIR');
defined('T_GOTO') || Patchwork_PHP_Parser::createToken('T_GOTO');
defined('T_NS_C') || Patchwork_PHP_Parser::createToken('T_NS_C');
defined('T_NAMESPACE') || Patchwork_PHP_Parser::createToken('T_NAMESPACE');

/**
 * The Backport53Tokens parser backports tokens introduced in PHP 5.3.
 */
class Patchwork_PHP_Parser_Backport53Tokens extends Patchwork_PHP_Parser
{
    protected

    $backports,
    $dependencies = array('Backport54Tokens' => 'backports');


    function __construct(parent $parent)
    {
        parent::__construct($parent);

        $this->backports += array(
            'goto' => T_GOTO,
            '__dir__' => T_DIR,
            'namespace' => T_NAMESPACE,
            '__namespace__' => T_NS_C,
        );
    }
}

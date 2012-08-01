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
 * The CodePathElseEnlightner parser adds missing "else" to "if" for decision coverage.
 *
 * TODO: alternative syntax
 */
class Patchwork_PHP_Parser_CodePathElseEnlightener extends Patchwork_PHP_Parser
{
    protected

    $callbacks = array(
        '~tagIf' => array(T_IF, T_ELSEIF),
    ),
    $dependencies = 'ControlStructBracketer';


    protected function tagIf(&$token)
    {
        $this->register(array('~tagConditionClose' => T_BRACKET_CLOSE));
    }

    protected function tagConditionClose(&$token)
    {
        $this->register('~tagBlockOpen');
    }

    protected function tagBlockOpen(&$token)
    {
        $this->unregister('~tagBlockOpen');
        if (':' === $token[0]) return;
        $this->register(array('~tagBlockClose' => T_BRACKET_CLOSE));
    }

    protected function tagBlockClose(&$token)
    {
        $token =& $this->getNextToken();

        if (T_ELSE !== $token[0] && T_ELSEIF !== $token[0])
        {
            $this->unshiftTokens(array(T_ELSE, 'else'), '{', ';', '}');
        }
    }
}

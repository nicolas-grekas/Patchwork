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

Parser::createToken('T_BRACKET_CLOSE');

/**
 * The BracketWatcher parser counts opening brackets and triggers callbacks on corresponding closing brackets.
 */
class BracketWatcher extends Parser
{
    protected

    $brackets = array(),
    $bracketsCount = 0,
    $callbacks = array(
        '~pushBracket' => array('{', '[', '('),
        'closeBracket' => array('}', ']', ')'),
        '~popBracket'  => array('}', ']', ')'),
    );


    protected function pushBracket(&$token)
    {
        ++$this->bracketsCount;
        $b =& $this->brackets[];

        switch ($token[0])
        {
        case '(': $b = ')'; break;
        case '[': $b = ']'; break;
        default: $b = '}'; break;
        }

        if (empty($this->tokenRegistry[T_BRACKET_CLOSE])) return;

        $b = array($b, $this->tokenRegistry[T_BRACKET_CLOSE]);
        unset($this->tokenRegistry[T_BRACKET_CLOSE]);
    }

    protected function closeBracket(&$token)
    {
        $last = end($this->brackets);

        if (isset($last[1]) && $token[0] === $last[0])
        {
            // Bracket has registered on-close callbacks
            $this->tokenRegistry[T_BRACKET_CLOSE] =& $this->brackets[$this->bracketsCount - 1][1];
            return T_BRACKET_CLOSE;
        }
    }

    protected function popBracket(&$token)
    {
        unset($this->tokenRegistry[T_BRACKET_CLOSE]);

        --$this->bracketsCount;
        $last = array_pop($this->brackets);

        if (empty($last) || $token[0] !== $last[0])
        {
            // Brackets are not correctly balanced, code has a parse error.
            $this->setError("Brackets are not correctly balanced", E_USER_WARNING);
            $this->unregister($this->callbacks);
            $this->brackets = array();
        }
    }
}

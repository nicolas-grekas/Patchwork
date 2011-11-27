<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/lgpl.txt GNU/LGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Lesser General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/

Patchwork_PHP_Parser::createToken('T_CBRACKET',/* 'T_SBRACKET', 'T_RBRACKET',*/ 'T_BRACKET_CLOSE');

/**
 * The BracketBalancer parser counts opening brackets and triggers callbacks on corresponding closing brackets.
 *
 * TODO: Currently only curly brackets are handled, but square and rounded brackets are to be added,
 *       with a mechanism preventing registering them when no dependent parser needs them.
 */
class Patchwork_PHP_Parser_BracketBalancer extends Patchwork_PHP_Parser
{
    protected

    $brackets = array(),
    $callbacks = array(
        'pushBracket' => array('{',/* '[', '('*/),
        'popBracket'  => array('}',/* ']', ')'*/),
    );


    protected function pushBracket(&$token)
    {
        switch ($token[0])
        {
        case '{': $this->brackets[] = '}'; $t = T_CBRACKET; break;
        case '[': $this->brackets[] = ']'; $t = T_SBRACKET; break;
        case '(': $this->brackets[] = ')'; $t = T_RBRACKET; break;
        }

        if (isset($this->tokenRegistry[$t]) || isset($this->tokenRegistry[T_BRACKET_CLOSE]))
        {
            $this->register(array('tagAfterOpen' => -$t));
            return $t;
        }
    }

    protected function tagAfterOpen(&$token)
    {
        $this->unregister(array(__FUNCTION__ => array(-T_CBRACKET,/* -T_SBRACKET, -T_RBRACKET*/)));
        if (empty($this->tokenRegistry[T_BRACKET_CLOSE])) return;
        $token =& $this->brackets[count($this->brackets) - 1];
        $token = (array) $token;
        $token[1] = $this->tokenRegistry[T_BRACKET_CLOSE];
        unset($this->tokenRegistry[T_BRACKET_CLOSE]);
    }

    protected function popBracket(&$token)
    {
        $last = array_pop($this->brackets);

        if (empty($last) || $token[0] !== $last[0])
        {
            // Brackets are not correctly balanced, code has a parse error.
            $this->unregister($this->callbacks);
        }
        else if (isset($last[1]))
        {
            // Bracket has registered on-close callbacks
            $this->tokenRegistry[T_BRACKET_CLOSE] = $last[1];
            $this->register(array('tagAfterClose' => -T_BRACKET_CLOSE));
            return T_BRACKET_CLOSE;
        }
    }

    protected function tagAfterClose(&$token)
    {
        unset($this->tokenRegistry[T_BRACKET_CLOSE]);
    }
}

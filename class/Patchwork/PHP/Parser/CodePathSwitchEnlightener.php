<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2012 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/lgpl.txt GNU/LGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Lesser General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/

/**
 * The CodePathSwitchEnlightner parser instruments switch structures for branch coverage analysis.
 */
class Patchwork_PHP_Parser_CodePathSwitchEnlightener extends Patchwork_PHP_Parser
{
    protected

    $switchStack = array(),
    $callbacks = array(
        'tagSwitchOpen' => T_SWITCH,
        'tagCaseOpen' => T_CASE,
        '~tagCaseClose' => ':'
    ),
    $dependencies = array('CodePathSplitter' => 'structStack', 'BracketWatcher');


    protected function tagSwitchOpen(&$token)
    {
        $this->register(array('~tagSwitchClose' => T_BRACKET_CLOSE));
        $token[1] .= '($̊S' . (count($this->switchStack)+1) . '=';
    }

    protected function tagSwitchClose(&$token)
    {
        $token[1] .= ' or true)';
        $this->register('tagBlockOpen');
    }

    protected function tagBlockOpen(&$token)
    {
        $this->unregister('tagBlockOpen');
        $this->register(array('~tagBlockClose' => ':' === $token[0] ? T_ENDSWITCH : T_BRACKET_CLOSE));
        $this->switchStack[] =& $token;
    }

    protected function tagCaseOpen(&$token)
    {
        $token[1] .= '(';
    }

    protected function tagCaseClose(&$token)
    {
        end($this->structStack);

        switch ($this->prevType)
        {
        case T_DEFAULT: $this->switchStack[count($this->switchStack)-1] =& $token[0];
        case T_ELSE:
        case '?':
            return;

        case '-': prev($this->structStack);
        case ']':
        case ')': prev($this->structStack);
        }

        switch (current($this->structStack))
        {
        case '?':
        case T_IF:
        case T_ELSEIF:
        case T_FOR:
        case T_FOREACH:
        case T_SWITCH:
        case T_WHILE:
            return;
        }

        $token[1] = ' )==$̊S' . count($this->switchStack) . " and\n\t\t(1?1:1)\n\t" . $token[1];
    }

    protected function tagBlockClose(&$token)
    {
        $this->register(array('~tagBlockClose' => T_ENDSWITCH));

        $token =& $this->switchStack[count($this->switchStack)-1];

        if (is_array($token))
        {
            $token[1] .= "\n\tdefault:\n\t\t(1?1:1);break;";
        }

        array_pop($this->switchStack);
    }
}

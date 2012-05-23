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
 *
 * TODO: annotation for switches where "default" can not be jumped to because "cases" cover all accessible possibilities.
 */
class Patchwork_PHP_Parser_CodePathSwitchEnlightener extends Patchwork_PHP_Parser
{
    protected

    $switchStack = array(),
    $skipNextColon = false,
    $callbacks = array(
        'tagSwitchOpen' => T_SWITCH,
        '~tagCaseOpen' => T_CASE,
        'tagCaseClose' => ':',
    ),

    $structStack,
    $dependencies = array(
        'CodePathSplitter' => 'structStack',
        'CaseColonEnforcer',
        'BracketWatcher',
    );


    protected function tagSwitchOpen(&$token)
    {
        $this->register(array('~tagSwitchClose' => T_BRACKET_CLOSE));
        $this->switchStack[] = false;
        $token[1] .= '($̊S' . count($this->switchStack) . '=';
    }

    protected function tagSwitchClose(&$token)
    {
        $token[1] .= ' or true)';
        $this->register('tagBlockOpen');
    }

    protected function tagBlockOpen(&$token)
    {
        $this->unregister(__FUNCTION__);
        $this->register(array('tagBlockClose' => ':' === $token[0] ? T_ENDSWITCH : T_BRACKET_CLOSE));
    }

    protected function tagCaseOpen(&$token)
    {
        $this->skipNextColon or $token[1] .= '(';
    }

    protected function tagCaseClose(&$token)
    {
        if ($this->skipNextColon)
        {
            $this->skipNextColon = false;
            return;
        }

        '-' === end($this->structStack) and prev($this->structStack);

        switch ($this->prevType)
        {
        case T_DEFAULT: $this->switchStack[count($this->switchStack)-1] = true;
        case T_ELSE:
        case '?':
            return;

        case ')':
            switch (current($this->structStack))
            {
            case T_IF:
            case T_ELSEIF:
            case T_FOR:
            case T_FOREACH:
            case T_SWITCH:
            case T_WHILE:
                return;
            }
            // No break;
        case ']':
            prev($this->structStack);
            break;
        }

        if ('?' === current($this->structStack)) return;

        $this->skipNextColon = true;

        end($this->types);
        $this->texts[key($this->types)] .= ')==$̊S' . count($this->switchStack);

        return $this->unshiftTokens(
            array(T_LOGICAL_AND, ' and'), array(T_LNUMBER, '(1?1:1)'), $token
        );
    }

    protected function tagBlockClose(&$token)
    {
        $this->unregister(array(__FUNCTION__ => array(T_ENDSWITCH, T_BRACKET_CLOSE)));

        $this->skipNextColon = true;

        $n = false === array_pop($this->switchStack) ? '(1?1:1) /*No matching case*/' : '(0?0:0) /*Jump to default*/';

        return $this->unshiftTokens(
            array(T_CASE, 'case'), array(T_WHITESPACE, ' '), array(T_LNUMBER, '(1?1:1)'), array(T_WHITESPACE, ' '),
            array(T_LOGICAL_AND, 'and'), array(T_LNUMBER, $n), ':', $token
        );
    }
}

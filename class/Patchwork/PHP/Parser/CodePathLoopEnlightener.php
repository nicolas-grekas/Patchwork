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
 * The CodePathLoopEnlightner parser instruments loops for loop coverage analysis.
 *
 * TODO: alternative syntax
 * TODO: annotation for loops that can not be skipped
 */
class Patchwork_PHP_Parser_CodePathLoopEnlightener extends Patchwork_PHP_Parser
{
    protected

    $loopStack = array(),
    $callbacks = array(
        '~tagLoop' => array(T_FOR, T_FOREACH, T_WHILE, T_DO),
    ),
    $dependencies = 'ControlStructBracketer';


    protected function tagLoop(&$token)
    {
        if (T_WHILE === $token[0] && T_DO === end($this->loopStack))
        {
            $this->register(array('~tagDoWhileClose' => T_BRACKET_CLOSE));
        }
        else
        {
            if (T_FOR === $token[0])
            {
                $n = array();

                do
                {
                    $t = $this->getNextToken($i);
                    if (isset($t[1])) break;
                    $n[] = $t[0];
                } while (count($n) < 3);

                if (array('(',';',';') === $n) return;
            }

            $this->loopStack[] = $token[0];
            $token[1] = '$̊L' . count($this->loopStack) . '=0;' . $token[1];
            if (T_DO === $token[0]) $this->register('~tagBlockOpen');
            else $this->register(array('~tagConditionClose' => T_BRACKET_CLOSE));
        }
    }

    protected function tagConditionClose(&$token)
    {
        $this->register('~tagBlockOpen');
    }

    protected function tagBlockOpen(&$token)
    {
        $this->unregister('~tagBlockOpen');
        if (':' === $token[0]) return;
        $this->unshiftTokens(array(T_LNUMBER, '++$̊L' . count($this->loopStack) . ';'));
        if (T_DO !== end($this->loopStack)) $this->register(array('~tagBlockClose' => T_BRACKET_CLOSE));
    }

    protected function tagDoWhileClose(&$token)
    {
        $this->register('~tagBlockClose');
    }

    protected function tagBlockClose(&$token)
    {
        $this->unregister('~tagBlockClose');
        $v = '$̊L' . count($this->loopStack);

        $this->unshiftTokens(
            array(T_LNUMBER, "({$v} >= 2)"), array(T_LOGICAL_AND, 'and'), array(T_LNUMBER, '(2?2:2) /*Loop repeated*/'), ';'
        );

        if (T_DO !== array_pop($this->loopStack))
        {
            $this->unshiftTokens(
                array(T_LNUMBER, "({$v} == 0)"), array(T_LOGICAL_AND, 'and'), array(T_LNUMBER, '(0?0:0) /*Loop skipped*/'), ';'
            );
        }
    }
}

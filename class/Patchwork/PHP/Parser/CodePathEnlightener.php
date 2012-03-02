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
 * The CodePathEnlightner parser explicits implicit code paths so that code coverage can see them.
 *
 * TODO: alternative syntax.
 */
class Patchwork_PHP_Parser_CodePathEnlightener extends Patchwork_PHP_Parser
{
    protected

    $loopStack = array(),
    $callbacks = array(
        '~tagLoop' => array(T_FOR, T_FOREACH, T_WHILE, T_DO),
        '~tagIf' => array(T_IF, T_ELSEIF),
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
            $token[1] = '$̊' . count($this->loopStack) . '=0;' . $token[1];
            if (T_DO === $token[0]) $this->register('~tagLoopOpen');
            else $this->register(array('~tagLoopTestClose' => T_BRACKET_CLOSE));
        }
    }

    protected function tagLoopTestClose(&$token)
    {
        $this->register('~tagLoopOpen');
    }

    protected function tagLoopOpen(&$token)
    {
        $this->unregister('~tagLoopOpen');
        if (':' === $token[0]) return;
        $this->unshiftTokens(array(T_LNUMBER, '++$̊' . count($this->loopStack) . ';'));
        if (T_DO !== end($this->loopStack)) $this->register(array('~tagLoopClose' => T_BRACKET_CLOSE));
    }

    protected function tagDoWhileClose(&$token)
    {
        $this->register('~tagLoopClose');
    }

    protected function tagLoopClose(&$token)
    {
        $this->unregister('~tagLoopClose');
        $v = '$̊' . count($this->loopStack);

        $this->unshiftTokens(
            array(T_LNUMBER, "({$v} >= 2) "), array(T_LOGICAL_AND, 'and'), array(T_LNUMBER, ' (2?2:2) /*Loop repeated*/'), ';'
        );

        if (T_DO !== array_pop($this->loopStack))
        {
            $this->unshiftTokens(
                array(T_LNUMBER, "({$v} == 0) "), array(T_LOGICAL_AND, 'and'), array(T_LNUMBER, ' (0?0:0) /*Loop skipped*/'), ';'
            );
        }
    }


    protected function tagIf(&$token)
    {
        $this->register(array('~tagIfTestClose' => T_BRACKET_CLOSE));
    }

    protected function tagIfTestClose(&$token)
    {
        $this->register('~tagIfOpen');
    }

    protected function tagIfOpen(&$token)
    {
        $this->unregister('~tagIfOpen');
        if (':' === $token[0]) return;
        $this->register(array('~tagIfClose' => T_BRACKET_CLOSE));
    }

    protected function tagIfClose(&$token)
    {
        $token =& $this->getNextToken();

        if (T_ELSE !== $token[0] && T_ELSEIF !== $token[0])
        {
            $this->unshiftTokens(array(T_ELSE, 'else '), '{', array(T_LNUMBER, '(0?0:0)'), ';', '}');
        }
    }
}

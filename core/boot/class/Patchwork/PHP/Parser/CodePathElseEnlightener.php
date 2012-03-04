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

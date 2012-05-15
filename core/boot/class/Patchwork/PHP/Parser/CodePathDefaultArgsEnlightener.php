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
 * The CodePathDefaultArgsEnlightener parser instruments functions for default arguments coverage analysis.
 */
class Patchwork_PHP_Parser_CodePathDefaultArgsEnlightener extends Patchwork_PHP_Parser
{
    protected

    $args = array(),
    $callbacks = array(
        '~tagFunction' => T_FUNCTION,
    ),
    $dependencies = 'BracketWatcher';


    protected function tagFunction(&$token)
    {
        $this->args = array();

        $this->register($this->callbacks = array(
            'tagArgVar' => T_VARIABLE,
            'tagArgDefault' => '=',
            'tagArgsClose' => T_BRACKET_CLOSE,
        ));
    }

    protected function tagArgVar(&$token)
    {
        $this->args[] = false;
    }

    protected function tagArgDefault(&$token)
    {
        $this->args[count($this->args) - 1] = true;
    }

    protected function tagArgsClose(&$token)
    {
        $this->unregister($this->callbacks);

        if ($this->args && true === end($this->args))
        {
            $this->register('tagBlockOpen');
        }
    }

    protected function tagBlockOpen(&$token)
    {
        $this->unregister(__FUNCTION__);

        if ('{' === $token[1])
        {
            do array_pop($this->args);
            while (true === end($this->args));

            $this->unshiftTokens(
                array(T_LNUMBER, "(func_num_args() <= " . count($this->args) . ")"), array(T_LOGICAL_AND, 'and'), array(T_LNUMBER, '(0?0:0) /*All default args used*/'), ';'
            );
        }
        else if (T_USE === $token[0])
        {
            $this->register(array('tagArgsClose' => T_BRACKET_CLOSE));
        }
    }
}

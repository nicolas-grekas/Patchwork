<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */
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

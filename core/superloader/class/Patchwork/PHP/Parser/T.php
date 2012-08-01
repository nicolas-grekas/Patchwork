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
 * The T parser warns when function T() is used with a concatenation inside its argument.
 */
class Patchwork_PHP_Parser_T extends Patchwork_PHP_Parser
{
    protected

    $callbacks = array('tagT' => T_USE_FUNCTION),

    $nsResolved, $expressionValue,
    $dependencies = array(
        'BracketWatcher',
        'NamespaceInfo' => 'nsResolved',
        'ConstantExpression' => 'expressionValue',
    );


    protected function tagT(&$token)
    {
        if ('\T' === strtoupper($this->nsResolved))
        {
            ++$this->index;

            if ($this->dependencies['ConstantExpression']->nextExpressionIsConstant())
            {
                if ($_SERVER['PATCHWORK_LANG'])
                {
                    // Add the string to the translation table
                    TRANSLATOR::get($this->expressionValue, $_SERVER['PATCHWORK_LANG'], false);
                }
            }
            else
            {
                $this->register($this->callbacks = array(
                    'tagConcat' => array(T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES, '.'),
                    'tagTClose' => T_BRACKET_CLOSE,
                ));
            }

            --$this->index;
        }
    }

    protected function tagConcat(&$token)
    {
        $this->setError("Usage of T() is potentially divergent, please avoid string concatenation", E_USER_NOTICE);
        $this->tagTClose($token);
    }

    protected function tagTClose(&$token)
    {
        $this->unregister($this->callbacks);
    }
}

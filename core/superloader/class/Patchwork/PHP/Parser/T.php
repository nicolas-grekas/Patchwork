<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2012 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/

/**
 * The T parser warns when function T() is used with a concatenation inside its argument.
 */
class Patchwork_PHP_Parser_T extends Patchwork_PHP_Parser
{
    protected

    $callbacks = array('tagT' => T_USE_FUNCTION),
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
                $this->register(array(
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
        $this->unregister(array('tagConcat' => array(T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES, '.')));
    }
}

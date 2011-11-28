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

/**
 * The Globalizer parser automatically globalizes a set of variables.
 */
class Patchwork_PHP_Parser_Globalizer extends Patchwork_PHP_Parser
{
    protected

    $autoglobals = array(),
    $callbacks   = array(
        'tagScopeOpen'   => T_SCOPE_OPEN,
        'tagAutoglobals' => T_VARIABLE,
    ),
    $dependencies = array('ScopeInfo' => 'scope');


    function __construct(parent $parent, $autoglobals)
    {
        foreach ((array) $autoglobals as $autoglobals)
        {
            if ( !isset(${substr($autoglobals, 1)})
                || '$autoglobals' === $autoglobals
                || '$parent'      === $autoglobals )
            {
                $this->autoglobals[$autoglobals] = 1;
            }
        }

        parent::__construct($parent);
    }

    protected function tagScopeOpen(&$token)
    {
        $this->scope->autoglobals = array();
        $this->register(array('tagScopeClose' => -T_BRACKET_CLOSE));
    }

    protected function tagAutoglobals(&$token)
    {
        if (isset($this->autoglobals[$token[1]]) && T_DOUBLE_COLON !== $this->lastType)
        {
            $this->scope->autoglobals[$token[1]] = 1;
        }
    }

    protected function tagScopeClose(&$token)
    {
        if ($this->scope->autoglobals) switch ($this->scope->type)
        {
        case T_OPEN_TAG: case T_FUNCTION: case T_NAMESPACE:
            $this->scope->token[1] .= 'global ' . implode(',', array_keys($this->scope->autoglobals)) . ';';
        }
    }
}

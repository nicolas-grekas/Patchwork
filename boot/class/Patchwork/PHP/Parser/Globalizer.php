<?php /***** vi: set encoding=utf-8 expandtab shiftwidth=4: ****************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class Patchwork_PHP_Parser_Globalizer extends Patchwork_PHP_Parser
{
    protected

    $autoglobals = array(),
    $callbacks   = array(
        'tagScopeOpen'   => T_SCOPE_OPEN,
        'tagAutoglobals' => T_VARIABLE,
    ),
    $dependencies = array('Scoper' => 'scope');


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
        $this->register(array('tagScopeClose' => T_SCOPE_CLOSE));
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

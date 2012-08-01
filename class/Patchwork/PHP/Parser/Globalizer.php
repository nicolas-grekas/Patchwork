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

    $scope,
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
        $this->register(array('tagScopeClose' => T_BRACKET_CLOSE));
    }

    protected function tagAutoglobals(&$token)
    {
        if (isset($this->autoglobals[$token[1]]) && T_DOUBLE_COLON !== $this->prevType)
        {
            $this->scope->autoglobals[$token[1]] = 1;
        }
    }

    protected function tagScopeClose(&$token)
    {
        $this->unregister(array(__FUNCTION__ => T_BRACKET_CLOSE));
        if ($this->scope->autoglobals) switch ($this->scope->type)
        {
        case T_OPEN_TAG: case T_FUNCTION: case T_NAMESPACE:
            $this->scope->token[1] .= 'global ' . implode(',', array_keys($this->scope->autoglobals)) . ';';
        }
    }
}

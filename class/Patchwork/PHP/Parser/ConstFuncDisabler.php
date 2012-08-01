<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

// TODO: allow local usage of inline declared consts, functions and define()

/**
 * The ConstFuncDisabler parser emits a deprecation notice on namespaced functions or constants declarations.
 */
class Patchwork_PHP_Parser_ConstFuncDisabler extends Patchwork_PHP_Parser
{
    protected

    $callbacks = array('tagOpenTag' => T_SCOPE_OPEN),

    $scope, $namespace,
    $dependencies = array('ScopeInfo' => array('scope', 'namespace'));


    protected function tagOpenTag(&$token)
    {
        if (T_NAMESPACE === $this->scope->type && $this->namespace)
        {
            $this->register($this->callbacks = array(
                'tagConstFunc'  => array(T_NAME_FUNCTION, T_NAME_CONST),
                'tagScopeClose' => T_BRACKET_CLOSE,
            ));
        }
    }

    protected function tagConstFunc(&$token)
    {
        if (T_CLASS !== $this->scope->type && T_INTERFACE !== $this->scope->type && T_TRAIT !== $this->scope->type)
        {
            $this->setError("Namespaced functions and constants are deprecated, please use class constants and static methods instead", E_USER_DEPRECATED);
        }
    }

    protected function tagScopeClose(&$token)
    {
        $this->unregister($this->callbacks);
    }
}

<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

// TODO: parse for inline consts, functions and define()

/**
 * ConstFuncResolver statically resolves functions and constants to their fully namespaced name.
 *
 * Regular PHP resolves functions and constants at runtime, by looking in the current namespace
 * then in the global namespace. This parser alters this behavior by resolving them at compile
 * time. This can break some code, but it works if some convention is followed.
 * This allows deeper static code analysis for other parsers.
 */
class Patchwork_PHP_Parser_ConstFuncResolver extends Patchwork_PHP_Parser
{
    protected

    $openTag,
    $nsCode,
    $nsCodeLoader,
    $callbacks = array('tagOpenTag' => T_SCOPE_OPEN),

    $scope, $namespace,
    $dependencies = array('ScopeInfo' => array('scope', 'namespace'));


    function __construct(parent $parent, $ns_code_loader = null)
    {
        $this->nsCodeLoader = $ns_code_loader ? $ns_code_loader : array($this, 'nsCodeLoader');
        parent::__construct($parent);
    }

    protected function tagOpenTag(&$token)
    {
        if (T_NAMESPACE === $this->scope->type && $this->namespace)
        {
            $this->openTag =& $token;
            $this->register($this->callbacks = array(
                'tagConstFunct' => array(T_USE_FUNCTION, T_USE_CONSTANT),
                'tagScopeClose' => T_BRACKET_CLOSE,
            ));
        }
    }

    protected function tagConstFunct(&$token)
    {
        if (T_NS_SEPARATOR !== $this->prevType)
        {
            $this->unshiftTokens(array(T_NS_SEPARATOR, '\\'), $token);

            if ($this->nsCode = call_user_func($this->nsCodeLoader, isset($token[2][T_USE_FUNCTION]), $this->namespace, $token[1]))
                $this->unshiftTokens(array(T_NAMESPACE, 'namespace'));

            return false;
        }
    }

    protected function tagScopeClose(&$token)
    {
        $this->unregister($this->callbacks);

        if (is_string($this->nsCode))
        {
            $this->openTag[1] .= $this->nsCode . ';';
            $this->nsCode = false;
        }
    }

    protected function nsCodeLoader($is_func, $ns, $token)
    {
        // FIXME: This doesn't work in PHP 5.2 for namespaced functions and constants
        if ($is_func) return function_exists($ns . $token);
        else return defined($ns . $token);
    }
}

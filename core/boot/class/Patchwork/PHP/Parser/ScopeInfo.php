<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

Patchwork_PHP_Parser::createToken('T_SCOPE_OPEN');

/**
 * The ScopeInfo parser exposes scopes to dependend parsers.
 *
 * Scopes are typed as T_OPEN_TAG, T_NAMESPACE, T_FUNCTION, T_CLASS, T_INTERFACE and T_TRAIT, each
 * of these corresponding to the type of the token who opened the scope. For each scope, this
 * parser exposes this type alongside with a reference to its opening token and its parent scope.
 *
 * T_SCOPE_OPEN can be registered by dependend parsers and is emitted on scope opening tokens.
 * When T_BRACKET_CLOSE is registered within a T_SCOPE_OPEN, it matches its corresponding scope closing token.
 *
 * ScopeInfo eventually inherits removeNsPrefix(), namespace, nsResolved, nsPrefix properties from NamespaceInfo.
 */
class Patchwork_PHP_Parser_ScopeInfo extends Patchwork_PHP_Parser
{
    protected

    $scope     = false,
    $nextScope = T_OPEN_TAG,
    $callbacks = array(
        '~tagFirstScope' => array(T_OPEN_TAG, ';', '{'),
        'tagScopeOpen' => '{',
        'tagNamespace' => T_NAMESPACE,
        'tagEndScope' => T_ENDPHP,
        'tagFunction' => T_FUNCTION,
        'tagClass' => array(T_CLASS, T_INTERFACE, T_TRAIT),
    ),

    $namespace, $nsResolved, $nsPrefix,
    $dependencies = array(
        'BracketWatcher',
        'NamespaceInfo' => array('namespace', 'nsResolved', 'nsPrefix'),
        'Normalizer',
    );


    function removeNsPrefix()
    {
        empty($this->nsPrefix) || $this->dependencies['NamespaceInfo']->removeNsPrefix();
    }

    protected function tagFirstScope(&$token)
    {
        $t = $this->getNextToken();
        if (T_NAMESPACE === $t[0] || T_DECLARE === $t[0]) return;
        '{' !== $token[0] && $this->unshiftTokens(array('{', ''));
        $this->unregister(array('~tagFirstScope' => array(T_OPEN_TAG, ';', '{')));
    }

    protected function tagEndScope(&$token)
    {
        $this->unregister(array(__FUNCTION__ => T_ENDPHP));
        if ($this->scope) return $this->unshiftTokens(array('}', ''), $token);
    }

    protected function tagScopeOpen(&$token)
    {
        $this->unregister(array(__FUNCTION__ => '{'));
        $this->register(array('~tagScopeClose' => T_BRACKET_CLOSE));

        $this->scope = (object) array(
            'parent' => $this->scope,
            'type'   => $this->nextScope,
            'token'  => &$token,
        );

        return T_SCOPE_OPEN;
    }

    protected function tagScopeClose(&$token)
    {
        $this->scope = $this->scope->parent;
    }

    protected function tagClass(&$token)
    {
        $this->nextScope = $token[0];
        $this->register(array('tagScopeOpen' => '{'));
    }

    protected function tagFunction(&$token)
    {
        $this->nextScope = T_FUNCTION;
        $this->register(array(
            'tagSemiColon' => ';', // For abstracts methods
            'tagScopeOpen' => '{',
        ));
    }

    protected function tagNamespace(&$token)
    {
        switch ($this->prevType)
        {
        default: return;
        case ';':
        case '}':
        case T_OPEN_TAG:
            $t = $this->getNextToken();
            if (T_STRING === $t[0] || '{' === $t[0])
            {
                $this->nextScope = T_NAMESPACE;

                if ($this->scope)
                {
                    $this->register(array(
                        'tagFirstScope' => array(';', '{'),
                        'tagScopeOpen' => '{',
                    ));

                    if ('}' !== $this->prevType) return $this->unshiftTokens(array('}', ''), $token);
                }
            }
        }
    }

    protected function tagSemiColon(&$token)
    {
        $this->unregister(array(
            __FUNCTION__ => ';',
            'tagScopeOpen' => '{',
        ));
    }
}

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
        'tagFirstScope' => array(T_OPEN_TAG, ';', '{'),
        'tagScopeOpen'  => T_CBRACKET,
        'tagEndScope'   => T_ENDPHP,
        'tagNamespace'  => T_NAMESPACE,
        'tagFunction'   => T_FUNCTION,
        'tagClass'      => array(T_CLASS, T_INTERFACE, T_TRAIT),
    ),
    $dependencies = array(
        'BracketBalancer',
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
        $this->unregister(array(__FUNCTION__ => array(T_OPEN_TAG, ';', '{')));
    }

    protected function tagEndScope(&$token)
    {
        $this->unregister(array(__FUNCTION__ => T_ENDPHP));
        if ($this->scope) return $this->unshiftTokens(array('}', ''), $token);
    }

    protected function tagScopeOpen(&$token)
    {
        $this->unregister(array(__FUNCTION__ => T_CBRACKET));
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
        $this->register(array('tagScopeOpen' => T_CBRACKET));
    }

    protected function tagFunction(&$token)
    {
        $this->nextScope = T_FUNCTION;
        $this->register(array(
            'tagSemiColon' => ';', // For abstracts methods
            'tagScopeOpen' => T_CBRACKET,
        ));
    }

    protected function tagNamespace(&$token)
    {
        switch ($this->lastType)
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
                        'tagScopeOpen' => T_CBRACKET,
                    ));

                    if ('}' !== $this->lastType) return $this->unshiftTokens(array('}', ''), $token);
                }
            }
        }
    }

    protected function tagSemiColon(&$token)
    {
        $this->unregister(array(
            __FUNCTION__ => ';',
            'tagScopeOpen' => T_CBRACKET,
        ));
    }
}

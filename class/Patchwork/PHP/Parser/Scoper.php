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


Patchwork_PHP_Parser::createToken('T_SCOPE_OPEN');  // new scope opening
Patchwork_PHP_Parser::createToken('T_SCOPE_CLOSE'); // scope closing - has to be registered within its corresponding T_SCOPE_OPEN


class Patchwork_PHP_Parser_Scoper extends Patchwork_PHP_Parser
{
    protected

    $curly     = 0,
    $scope     = false,
    $scopes    = array(),
    $nextScope = T_OPEN_TAG,
    $callbacks = array(
        'tagFirstScope' => array(T_OPEN_TAG, ';', '{'),
        'tagScopeClose' => array(T_ENDPHP  , '}'),
        'tagNamespace'  => T_NAMESPACE,
        'tagFunction'   => T_FUNCTION,
        'tagClass'      => array(T_CLASS, T_INTERFACE),
    ),
    $dependencies = 'Normalizer';


    protected function tagFirstScope(&$token)
    {
        $t = $this->getNextToken();

        if (T_NAMESPACE === $t[0] || T_DECLARE === $t[0]) return;

        $this->unregister(array(__FUNCTION__ => array(T_OPEN_TAG, ';', '{')));
        $this->  register(array('tagScopeOpen'  => '{'));

        return $this->tagScopeOpen($token);
    }

    protected function tagScopeOpen(&$token)
    {
        if ($this->nextScope)
        {
            $this->scope = (object) array(
                'parent' => $this->scope,
                'type'   => $this->nextScope,
                'token'  => &$token,
            );

            $this->nextScope = false;
            $this->scopes[] = array($this->curly, array());
            $this->curly = 0;

            if (isset($this->tokenRegistry[T_SCOPE_OPEN]))
            {
                unset($this->tokenRegistry[T_SCOPE_CLOSE]);
                $this->unshiftTokens(array(T_WHITESPACE, ''));
                $this->register(array('tagAfterScopeOpen' => T_WHITESPACE));
                return T_SCOPE_OPEN;
            }
        }
        else ++$this->curly;
    }

    protected function tagAfterScopeOpen(&$token)
    {
        $this->unregister(array(__FUNCTION__ => T_WHITESPACE));

        if (empty($this->tokenRegistry[T_SCOPE_CLOSE])) return;

        $this->scopes[count($this->scopes) - 1][1] = $this->tokenRegistry[T_SCOPE_CLOSE];
        unset($this->tokenRegistry[T_SCOPE_CLOSE]);
    }

    protected function tagScopeClose(&$token)
    {
        if (0 > --$this->curly && $this->scopes)
        {
            list($this->curly, $c) = array_pop($this->scopes);

            if ($c)
            {
                $this->tokenRegistry[T_SCOPE_CLOSE] = array_reverse($c);
                $this->unshiftTokens(array(T_WHITESPACE, ''));
                $this->register(array('tagAfterScopeClose' => T_WHITESPACE));
                return T_SCOPE_CLOSE;
            }

            $this->scope = $this->scope->parent;
        }
    }

    protected function tagAfterScopeClose(&$token)
    {
        $this->unregister(array(__FUNCTION__ => T_WHITESPACE));
        unset($this->tokenRegistry[T_SCOPE_CLOSE]);
        $this->scope = $this->scope->parent;
    }

    protected function tagClass(&$token)
    {
        $this->nextScope = $token[0];
    }

    protected function tagFunction(&$token)
    {
        $this->nextScope = T_FUNCTION;
        $this->register(array('tagSemiColon'  => ';')); // For abstracts methods
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
                    $this->  register(array('tagFirstScope' => array(';', '{')));
                    $this->unregister(array('tagScopeOpen'  => '{'));
                    return $this->tagScopeClose($token);
                }
            }
        }
    }

    protected function tagSemiColon(&$token)
    {
        $this->unregister(array(__FUNCTION__ => ';'));
        $this->nextScope = false;
    }
}

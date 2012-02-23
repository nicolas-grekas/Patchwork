<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2012 Nicolas Grekas. All rights reserved.
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
 * The NamespaceRemover parser backports namespaces introduced in PHP 5.3.
 *
 * It does so by resolving then removing namespace declarations and replacing namespace separators by underscores.
 */
class Patchwork_PHP_Parser_NamespaceRemover extends Patchwork_PHP_Parser
{
    protected

    $aliasAdd   = false,
    $callbacks  = array(
        'tagNs'     => T_NAMESPACE,
        'tagNsSep'  => T_NS_SEPARATOR,
        'tagNsUse'  => array(T_USE_CLASS, T_USE_FUNCTION, T_USE_CONSTANT, T_TYPE_HINT),
        'tagNsName' => array(T_NAME_CLASS, T_NAME_FUNCTION),
        'tagNew'    => T_NEW,
        'tagConst'  => T_CONST,
    ),
    $dependencies = array(
        'BracketBalancer' => 'brackets',
        'ConstFuncResolver',
        'ClassInfo' => array('class', 'scope', 'namespace'),
        'NamespaceResolver',
    );


    function __construct(parent $parent, $aliasAdd = false)
    {
        $this->aliasAdd = $aliasAdd;
        parent::__construct($parent);
    }

    protected function tagNs(&$token)
    {
        if (!isset($token[2][T_NAME_NS])) return;

        $this->register('tagNsEnd');
        $token[1] = ' ';
    }

    protected function tagNsEnd(&$token)
    {
        switch ($token[0])
        {
        case '{': $this->register(array('tagNsClose' => T_BRACKET_CLOSE));
        case ';':
        case $this->prevType:
            $this->unregister(__FUNCTION__);
            if ($this->prevType === $token[0]) return;
        }

        $token[1] = '';
    }

    protected function tagNsClose(&$token)
    {
        $token[1] = '';
    }

    protected function tagNsSep(&$token)
    {
        if (T_STRING === $this->prevType) $token[1] = strtr($token[1], '\\', '_');
        else if (T_NS_SEPARATOR !== $this->prevType) $token[1] = ' ';
    }

    protected function tagNsUse(&$token)
    {
        $token[1] = strtr($token[1], '\\', '_');
    }

    protected function tagNsName(&$token)
    {
        if ($this->namespace)
        {
            switch ($this->scope->type) {case T_CLASS: case T_INTERFACE: case T_TRAIT: return;}

            if (isset($token[2][T_NAME_CLASS]))
            {
                $this->class->nsName = strtr($this->class->nsName, '\\', '_');
                $this->aliasAdd && $this->scope->token[1] .= "{$this->aliasAdd}('{$this->namespace}{$this->class->name}');";
                $this->class->name = $this->class->nsName;
            }

            $this->texts[count($this->texts) - 1] .= strtr($this->namespace, '\\', '_');
        }
    }

    protected function tagNew(&$token)
    {
        // Fixes `new $foo`, when $foo = 'ns\class';
        // TODO: new ${...}, new $foo[...] and new $foo->...

        $t =& $this->getNextToken($n);

        if (T_VARIABLE === $t[0])
        {
            $n = $this->getNextToken($n);

            if ('[' !== $n[0] && T_OBJECT_OPERATOR !== $n[0])
            {
                $t[1] = "\${is_string($\x9D={$t[1]})&&($\x9D=strtr(isset($\x9D[0])&&'\\\\'===$\x9D[0]?substr($\x9D,1):$\x9D,'\\\\','_'))?\"\x9D\":\"\x9D\"}";
            }
        }
    }

    protected function tagConst(&$token)
    {
        switch ($this->scope->type)
        {
        case T_OPEN_TAG:
        case T_NAMESPACE:
            $token[1] = 'define(';

            $this->getNextToken($i); // The only valid token here is a T_NON_SEMANTIC
            $this->tokens[$i-2][1] .= "'" . strtr($this->namespace, '\\', '_');

            $this->constBracketLevel = count($this->brackets);
            $this->register($this->callbacks = array(
                'tagConstName' => T_STRING,
                'tagConstEqual' => '=',
                'tagConstEnd' => array(';', ','),
            ));
        }
    }

    protected function tagConstName(&$token)
    {
        $this->unshiftTokens(array(T_WHITESPACE, "'"));
    }

    protected function tagConstEqual(&$token)
    {
        $token[1] = ',';
    }

    protected function tagConstEnd(&$token)
    {
        if (count($this->brackets) === $this->constBracketLevel)
        {
            if (';' === $token[0])
            {
                $this->unregister($this->callbacks);
                $token[1] = ")" . $token[1];
            }
            else
            {
                $token[1] = ");define(";
                $this->getNextToken($i);
                $this->tokens[$i-2][1] .= "'" . strtr($this->namespace, '\\', '_');
            }
        }
    }
}

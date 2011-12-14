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
 * The NamespaceRemover parser backports namespaces introduced in PHP 5.3.
 *
 * It does so by resolving then removing namespace declarations and replacing namespace separators by underscores.
 */
class Patchwork_PHP_Parser_NamespaceRemover extends Patchwork_PHP_Parser
{
    protected

    $namespaceBackup,
    $aliasAdd   = false,
    $callbacks  = array(
        'tagNs'     => T_NAMESPACE,
        'tagNsSep'  => T_NS_SEPARATOR,
        'tagNsUse'  => array(T_USE_CLASS, T_USE_FUNCTION, T_USE_CONSTANT, T_TYPE_HINT),
        'tagNsName' => array(T_NAME_CLASS, T_NAME_FUNCTION),
        'tagNew'    => T_NEW,
    ),
    $dependencies = array(
        'ConstFuncResolver',
        'ClassInfo' => array('class', 'scope', 'namespace', 'nsResolved'),
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
        case $this->lastType:
            $this->namespaceBackup = $this->namespace;
            $this->namespace = strtr($this->namespace, '\\', '_');
            $this->unregister(__FUNCTION__);
            if ($this->lastType === $token[0]) return;
        }

        $token[1] = '';
    }

    protected function tagNsClose(&$token)
    {
        $token[1] = '';
    }

    protected function tagNsSep(&$token)
    {
        if (T_STRING === $this->lastType) $token[1] = strtr($token[1], '\\', '_');
        else if (T_NS_SEPARATOR !== $this->lastType) $token[1] = ' ';
    }

    protected function tagNsUse(&$token)
    {
        $token[1] = strtr($token[1], '\\', '_');
        $this->nsResolved = strtr($this->nsResolved, '\\', '_');
        '_' === substr($this->nsResolved, 0, 1) && $this->nsResolved[0] = '\\';
    }

    protected function tagNsName(&$token)
    {
        if ($this->namespace && T_CLASS !== $this->scope->type && T_INTERFACE !== $this->scope->type && T_TRAIT !== $this->scope->type)
        {
            if (isset($token[2][T_NAME_CLASS]))
            {
                $this->class->nsName = strtr($this->class->nsName, '\\', '_');
                $this->aliasAdd && $this->scope->token[1] .= "{$this->aliasAdd}('{$this->namespaceBackup}{$this->class->name}');";
                $this->class->name   = $this->class->nsName;
            }

            $this->texts[count($this->texts) - 1] .= $this->namespace;
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
}

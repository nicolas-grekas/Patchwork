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


class patchwork_PHP_Parser_namespaceRemover extends patchwork_PHP_Parser
{
    protected

    $namespaceBackup,
    $aliasAdd   = false,
    $callbacks  = array(
        'tagNs'     => T_NAMESPACE,
        'tagNsSep'  => T_NS_SEPARATOR,
        'tagNsUse'  => array(T_USE_CLASS, T_USE_FUNCTION, T_USE_CONSTANT, T_TYPE_HINT),
        'tagNsName' => array(T_NAME_CLASS, T_NAME_FUNCTION),
    ),
    $dependencies = array('constFuncResolver', 'namespaceResolver', 'classInfo' => array('class', 'scope', 'namespace', 'nsResolved'));


    function __construct(parent $parent, $aliasAdd = false)
    {
        $this->aliasAdd = $aliasAdd;
        parent::__construct($parent);
    }

    protected function tagNs(&$token)
    {
        if (isset($token[2][T_NAME_NS]))
        {
            $this->register('tagNsEnd');
            $token[1] = ' ';
        }
    }

    protected function tagNsEnd(&$token)
    {
        switch ($token[0])
        {
        case '{':
        case ';':
        case $this->lastType:
            $this->namespaceBackup = $this->namespace;
            $this->namespace = strtr($this->namespace, '\\', '_');
            $this->unregister(__FUNCTION__);
            if (';' !== $token[0]) return;
        }

        $token[1] = '';
    }

    protected function tagNsSep(&$token)
    {
        if (T_STRING === $this->lastType) $token[1] = strtr($token[1], '\\', '_');
        else if (T_NS_SEPARATOR !== $this->lastType) $token[1] = '';
    }

    protected function tagNsUse(&$token)
    {
        $token[1] = strtr($token[1], '\\', '_');
        $this->nsResolved = strtr($this->nsResolved, '\\', '_');
        '_' === substr($this->nsResolved, 0, 1) && $this->nsResolved[0] = '\\';
    }

    protected function tagNsName(&$token)
    {
        if ($this->namespace && T_CLASS !== $this->scope->type && T_INTERFACE !== $this->scope->type)
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
}

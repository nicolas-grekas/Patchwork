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
 * NamespaceInfo exposes each token's namespace context to dependend parsers.
 *
 * Exposed namespace context info are:
 * - namespace: string containing the current namespace
 * - nsResolved: fully namespace resolved identifier for the current class, function or constant token
 * - nsAliases: map of local alias to fully resolved identifiers in use in the current namespace
 *
 * Inherited from StringInfo are nsPrefix and removeNsPrefix().
 */
class Patchwork_PHP_Parser_NamespaceInfo extends Patchwork_PHP_Parser
{
    protected

    $namespace  = '',
    $nsResolved = '',
    $nsAliases  = array(),
    $nsUse      = array(),
    $callbacks  = array(
        'tagNs'        => T_NAMESPACE,
        'tagUse'       => T_USE,
        'tagNsResolve' => array(T_USE_CLASS, T_USE_FUNCTION, T_USE_CONSTANT, T_TYPE_HINT),
    ),

    $nsPrefix,
    $dependencies = array('StringInfo' => 'nsPrefix');


    protected static

    $nsCallbacks  = array(
        'tagNsName' => array(T_STRING, T_NS_SEPARATOR),
        'tagNsEnd'  => array('{', ';'),
    ),
    $useCallbacks = array(
        'tagUseAs'   => T_STRING,
        'tagUseNext' => array(',', ';'),
        'tagUseEnd'  => ';',
    );


    function removeNsPrefix()
    {
        empty($this->nsPrefix) || $this->dependencies['StringInfo']->removeNsPrefix();
    }

    protected function tagNs(&$token)
    {
        if (isset($token[2][T_NAME_NS]))
        {
            $this->namespace = '';
            $this->nsAliases = array();
            $this->register(self::$nsCallbacks);
        }
    }

    protected function tagNsName(&$token)
    {
        $this->namespace .= $token[1];
    }

    protected function tagNsEnd(&$token)
    {
        $this->namespace && $this->namespace .= '\\';
        $this->unregister(self::$nsCallbacks);
    }

    protected function tagUse(&$token)
    {
        if (')' !== $this->prevType)
        {
            $this->register(self::$useCallbacks);
        }
    }

    protected function tagUseAs(&$token)
    {
        if (T_AS === $this->prevType)
        {
            $this->nsAliases[$token[1]] = '\\' . implode('\\', $this->nsUse);
            $this->nsUse = array();
        }
        else
        {
            $this->nsUse[] = $token[1];
        }
    }

    protected function tagUseNext(&$token)
    {
        if ($this->nsUse)
        {
            $this->nsAliases[end($this->nsUse)] = '\\' . implode('\\', $this->nsUse);
            $this->nsUse = array();
        }
    }

    protected function tagUseEnd(&$token)
    {
        $this->unregister(self::$useCallbacks);
    }

    protected function tagNsResolve(&$token)
    {
        $this->nsResolved = $this->nsPrefix . $token[1];

        if ('' === $this->nsPrefix)
        {
            if (isset($token[2][T_USE_CLASS]) || isset($token[2][T_TYPE_HINT]))
            {
                $this->nsResolved = empty($this->nsAliases[$token[1]])
                    ? '\\' . $this->namespace . $token[1]
                    : $this->nsAliases[$token[1]];
            }
            else if (!$this->namespace)
            {
                $this->nsResolved = '\\' . $this->nsResolved;
            }
        }
        else if ('\\' !== $this->nsPrefix[0])
        {
            $a = explode('\\', $this->nsPrefix . $token[1], 2);

            if ('namespace' === $a[0])
            {
                $a[0] = $this->namespace ? substr('\\' . $this->namespace, 0, -1) : '';
            }
            else if (isset($this->nsAliases[$a[0]]))
            {
                $a[0] = $this->nsAliases[$a[0]];
            }
            else
            {
                $a[0] = '\\' . $this->namespace . $a[0];
            }

            $this->nsResolved = implode('\\', $a);
        }
    }
}

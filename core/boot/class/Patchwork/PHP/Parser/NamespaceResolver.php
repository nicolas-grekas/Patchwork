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
 * The NamespaceResolver parser replaces aliased identifiers by their fully namespace resolved name.
 *
 * It also removes local alias declarations as they are not needed anymore.
 */
class Patchwork_PHP_Parser_NamespaceResolver extends Patchwork_PHP_Parser
{
    protected

    $callbacks  = array(
        'tagUse'       => T_USE,
        'tagNsResolve' => array(T_USE_CLASS, T_USE_FUNCTION, T_USE_CONSTANT, T_TYPE_HINT),
    ),

    $namespace, $nsResolved, $nsPrefix,
    $dependencies = array('NamespaceInfo' => array('namespace', 'nsResolved', 'nsPrefix'));


    protected function tagUse(&$token)
    {
        if (')' !== $this->prevType)
        {
            $this->register('tagUseEnd');
            $token[1] = ' ';
        }
    }

    protected function tagUseEnd(&$token)
    {
        switch ($token[0])
        {
        case ';':
        case $this->prevType:
            $this->unregister(__FUNCTION__);
            if (';' !== $token[0]) return;
        }

        $token[1] = '';
    }

    protected function tagNsResolve(&$token)
    {
        if ('\\' !== $this->nsResolved[0])
        {
            $this->setError("Unresolved namespaced identifier ({$this->nsResolved})", E_USER_WARNING);
        }
        else if (isset($this->nsPrefix[0]) ? '\\' !== $this->nsPrefix[0] : ($this->namespace || $token[1] !== substr($this->nsResolved, 1)))
        {
            if (isset($this->nsPrefix[0])) $this->dependencies['NamespaceInfo']->removeNsPrefix();
            else if (('self' === $token[1] || 'parent' === $token[1]) && (isset($token[2][T_USE_CLASS]) || isset($token[2][T_TYPE_HINT]))) return;

            $this->unshiftTokens(array(T_STRING, substr($this->nsResolved, 1)));
            return $this->namespace && $this->unshiftTokens(array(T_NS_SEPARATOR, '\\'));
        }
    }
}

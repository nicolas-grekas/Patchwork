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


class Patchwork_PHP_Parser_NamespaceResolver extends Patchwork_PHP_Parser
{
    protected

    $callbacks  = array(
        'tagUse'       => T_USE,
        'tagNsResolve' => array(T_USE_CLASS, T_USE_FUNCTION, T_USE_CONSTANT, T_TYPE_HINT),
    ),
    $dependencies = array('StringInfo' => 'nsPrefix', 'NamespaceInfo' => array('namespace', 'nsResolved'));


    protected function tagUse(&$token)
    {
        if (')' !== $this->lastType)
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
        case $this->lastType:
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
            if ($this->nsPrefix) $this->dependencies['StringInfo']->removeNsPrefix();
            else if (('self' === $token[1] || 'parent' === $token[1]) && (isset($token[2][T_USE_CLASS]) || isset($token[2][T_TYPE_HINT]))) return;

            $this->unshiftTokens(array(T_STRING, substr($this->nsResolved, 1)));
            return $this->namespace && $this->unshiftTokens(array(T_NS_SEPARATOR, '\\'));
        }
    }
}

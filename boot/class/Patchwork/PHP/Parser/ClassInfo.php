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


class Patchwork_PHP_Parser_ClassInfo extends Patchwork_PHP_Parser
{
    protected

    $class     = false,
    $callbacks = array('tagClass' => array(T_CLASS, T_INTERFACE)),
    $dependencies = array('NamespaceInfo' => array('namespace', 'nsResolved'), 'Scoper' => 'scope');


    protected function tagClass(&$token)
    {
        $this->class = (object) array(
            'type'       => $token[1],
            'name'       => false,
            'nsName'     => false,
            'extends'    => false,
            'isFinal'    => T_FINAL    === $this->lastType,
            'isAbstract' => T_ABSTRACT === $this->lastType,
        );

        $this->callbacks = array(
            'tagClassName' => T_NAME_CLASS,
            'tagExtends'   => T_EXTENDS,
            'tagClassOpen' => T_SCOPE_OPEN,
        );

        $this->register();
    }

    protected function tagClassName(&$token)
    {
        $this->unregister(array(__FUNCTION__ => T_NAME_CLASS));
        $this->class->name   = $token[1];
        $this->class->nsName = $this->namespace . $token[1];
    }

    protected function tagExtends(&$token)
    {
        $this->register(array('tagExtendsName' => T_USE_CLASS));
    }

    protected function tagExtendsName(&$token)
    {
        $this->unregister(array(__FUNCTION__ => T_USE_CLASS));
        $this->class->extends = substr($this->nsResolved, 1);
    }

    protected function tagClassOpen(&$token)
    {
        $this->unregister();
        $this->register(array('tagClassClose' => T_SCOPE_CLOSE));
    }

    protected function tagClassClose(&$token)
    {
        $this->class = false;
    }
}

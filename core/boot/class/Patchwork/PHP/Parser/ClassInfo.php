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
 * The ClassInfo parser exposes class, interface and trait context to dependend parsers.
 *
 * It manages a property named class who contains info about the current class:
 * - type: class' type as T_CLASS, T_INTERFACE or T_TRAIT
 * - name: short name of the class without any namespace prefix
 * - nsName: fully namespace resolved class name
 * - extends: the class from which it extends, fully namespace resolved
 * - isFinal
 * - isAbstract
 *
 * It also inherits removeNsPrefix(), scope, namespace, nsResolved and nsPrefix properties from ScopeInfo
 */
class Patchwork_PHP_Parser_ClassInfo extends Patchwork_PHP_Parser
{
    protected

    $class     = false,
    $callbacks = array('tagClass' => array(T_CLASS, T_INTERFACE, T_TRAIT)),

    $scope, $namespace, $nsResolved, $nsPrefix,
    $dependencies = array('ScopeInfo' => array('scope', 'namespace', 'nsResolved', 'nsPrefix'));


    function removeNsPrefix()
    {
        empty($this->nsPrefix) || $this->dependencies['ScopeInfo']->removeNsPrefix();
    }

    protected function tagClass(&$token)
    {
        $this->class = (object) array(
            'type'       => $token[0],
            'name'       => false,
            'nsName'     => false,
            'extends'    => false,
            'isFinal'    => T_FINAL    === $this->prevType,
            'isAbstract' => T_ABSTRACT === $this->prevType,
        );

        $this->register($this->callbacks = array(
            'tagClassName' => T_NAME_CLASS,
            'tagExtends'   => T_EXTENDS,
            'tagClassOpen' => T_SCOPE_OPEN,
        ));
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
        $this->unregister($this->callbacks);
        $this->register(array('~tagClassClose' => T_BRACKET_CLOSE));
    }

    protected function tagClassClose(&$token)
    {
        $this->class = false;
    }
}

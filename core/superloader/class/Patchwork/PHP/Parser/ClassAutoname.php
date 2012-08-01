<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class Patchwork_PHP_Parser_ClassAutoname extends Patchwork_PHP_Parser
{
    protected

    $className,
    $callbacks = array('tagClass' => array(T_CLASS, T_INTERFACE, T_TRAIT));


    function __construct(parent $parent, $className)
    {
        parent::__construct($parent);

        $this->className = $className;
    }

    protected function tagClass(&$token)
    {
        $t = $this->getNextToken();

        if (T_STRING !== $t[0])
        {
            $this->setError("Class auto-naming is deprecated ({$this->className})", E_USER_DEPRECATED);

            $this->unshiftTokens(
                array(T_WHITESPACE, ' '),
                array(T_STRING, strtr(ltrim($this->className, '\\'), '\\', '_'))
            );

            $this->unregister($this->callbacks);
        }
    }
}

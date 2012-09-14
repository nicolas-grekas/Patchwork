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
 * ToString encapsulates __toString() methods inside a try/catch that allows
 * working around "__toString() must not throw an exception" error messages.
 */
class Patchwork_PHP_Parser_ToStringCatcher extends Patchwork_PHP_Parser
{
    protected

    $exceptionCallback,
    $callbacks = array('tagToString' => T_NAME_FUNCTION),

    $scope,
    $dependencies = array('ScopeInfo' => 'scope');


    function __construct(parent $parent, $exceptionCallback)
    {
        parent::__construct($parent);

        $exceptionCallback = ltrim($exceptionCallback, '\\');

        if (PHP_VERSION_ID >= 50300) $exceptionCallback = '\\' . $exceptionCallback;
        else $exceptionCallback = strtr($exceptionCallback, '\\', '_');

        $this->exceptionCallback = $exceptionCallback;
    }

    protected function tagToString(&$token)
    {
        if ((T_CLASS === $this->scope->type || T_TRAIT === $this->scope->type) && 0 === strcasecmp($token[1], '__toString'))
        {
            $this->register(array('tagToStringOpen' => T_SCOPE_OPEN));
        }
    }

    protected function tagToStringOpen(&$token)
    {
        $this->unregister(array(__FUNCTION__ => T_SCOPE_OPEN));
        $token[1] .= 'try{';
        $this->register(array('tagToStringClose' => T_BRACKET_CLOSE));
    }

    protected function tagToStringClose(&$token)
    {
        $this->unregister(array(__FUNCTION__ => T_BRACKET_CLOSE));
        $token[1] = '}catch(' .( PHP_VERSION_ID >= 50300 ? '\\' : '' ). 'Exception $e)'
            . '{'
                . 'return ' . $this->exceptionCallback . '($e);'
            . '}' . $token[1];
    }
}

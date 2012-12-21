<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork\PHP\Parser;

use Patchwork\PHP\Parser;

/**
 * The InvokeShim parser participates in backporting
 * the __invoke() magic method to PHP 5.2
 */
class InvokeShim extends Parser
{
    public

    $targetPhpVersionId = -50300;

    protected

    $bracket = 0,
    $signature = '',
    $arguments = array(),
    $callbacks = array('tagClassOpen' => T_SCOPE_OPEN),

    $class, $scope,
    $dependencies = array('ClassInfo' => array('class', 'scope'));


    protected function tagClassOpen(&$token)
    {
        if (T_CLASS === $this->scope->type)
        {
            $this->unregister($this->callbacks);
            $this->register($this->callbacks = array(
                'tagFunction' => T_FUNCTION,
                'tagClassClose' => T_BRACKET_CLOSE,
            ));
        }
    }

    protected function tagFunction(&$token)
    {
        T_CLASS === $this->scope->type && $this->register('tagFunctionName');
    }

    protected function tagFunctionName(&$token)
    {
        if ('&' === $token[0]) return;
        $this->unregister(__FUNCTION__);
        if (T_STRING !== $token[0]) return;

        if (0 === strcasecmp($token[1], '__invoke'))
        {
            $this->signature = ('&' === $this->prevType ? '&' : '') . '__' . strtr($this->class->nsName, '\\', '_') . '_invoke';
            $this->register('catchSignature');
            $this->unregister(array('tagFunction' => T_FUNCTION));
        }
    }

    protected function catchSignature(&$token)
    {
        if (T_VARIABLE === $token[0])
        {
            $this->arguments[] = '&' . $token[1];
        }
        else if ('(' === $token[0]) ++$this->bracket;
        else if (')' === $token[0]) --$this->bracket;

        if (50300 <= $this->targetPhpVersionId || (T_NS_SEPARATOR !== $token[0] && !isset($token[2][T_USE_NS])))
        {
            $this->signature .= $token[1];
        }

        $this->bracket <= 0 && $this->unregister(__FUNCTION__);
    }

    protected function tagClassClose(&$token)
    {
        $this->unregister($this->callbacks);

        if ('' !== $this->signature)
        {
            $n = "\$GLOBALS['i\x9D']";

            $token[1] .= 'function ' . $this->signature
              . '{'
              .   '${0}=' . $n . ';'
              .   $n . '=null;'
              .   'if(' . count($this->arguments) . '<func_num_args()){'
              .     '${1}=array(' . implode(',', $this->arguments) . ')+func_get_args();'
              .     'return call_user_func_array(array(${0},"__invoke"),${1});'
              .   '}else{'
              .     'return ${0}->__invoke(' . str_replace('&', '', implode(',', $this->arguments)) . ');'
              .   '}'
              . '}';

            $this->signature = '';
        }

        $this->bracket   = 0;
        $this->arguments = array();
        $this->callbacks = array('tagClassOpen' => T_SCOPE_OPEN);
        $this->register($this->callbacks);
    }
}

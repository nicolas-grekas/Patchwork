<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class Patchwork_PHP_Parser_Constructor4to5 extends Patchwork_PHP_Parser_InvokeShim
{
    protected

    $namespace,
    $dependencies = array('ClassInfo' => array('class', 'namespace', 'scope'));


    protected function tagClassOpen(&$token)
    {
        $this->namespace or parent::tagClassOpen($token);
    }

    protected function tagFunctionName(&$token)
    {
        if ('&' === $token[0]) return;
        $this->unregister(__FUNCTION__);
        if (T_STRING !== $token[0]) return;

        if (0 === strcasecmp($token[1], '__construct'))
        {
            $this->signature = '';
            $this->unregister($this->callbacks);
        }
        else if (empty($this->class->suffix)) {}
        else if (0 === strcasecmp($token[1], $this->class->nsName))
        {
            $this->signature = $token[1];
            $token[1] = '__construct';
            $this->register('catchSignature');
        }
        else if (0 === strcasecmp($token[1], $this->class->nsName . $this->class->suffix))
        {
            $this->setError("Constructor collision: __construct() must be defined and before {$token[1]}() in class {$this->class->nsName}", E_USER_ERROR);
        }
    }

    protected function tagClassClose(&$token)
    {
        if ('' !== $this->signature)
        {
            $n = $this->targetPhpVersionId < 50300 ? strtr($this->class->nsName, '\\', '_') : $this->class->nsName;

            $token[1] = 'function ' . $this->signature . '{'
                . 'if(' . count($this->arguments) . '<func_num_args()){'
                .   '${""}=array(' . implode(',', $this->arguments) . ')+func_get_args();'
                .   'call_user_func_array(array("' . $n . '","__construct"),${""});'
                . '}else{'
                .   $n . '::__construct(' . str_replace('&', '', implode(',', $this->arguments)) . ');'
                . '}}'
                . $token[1];

            $this->signature = '';
        }

        parent::tagClassClose($token);
    }
}

<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2012 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/lgpl.txt GNU/LGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Lesser General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class Patchwork_PHP_Parser_Constructor4to5 extends Patchwork_PHP_Parser
{
    protected

    $bracket = 0,
    $signature = '',
    $arguments = array(),
    $callbacks = array('tagClassOpen' => T_SCOPE_OPEN),
    $dependencies = array('ClassInfo' => array('class', 'namespace', 'scope'));


    protected function tagClassOpen(&$token)
    {
        if (!$this->namespace && T_CLASS === $this->scope->type)
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

    protected function catchSignature(&$token)
    {
        if (T_VARIABLE === $token[0])
        {
            $this->arguments[] = '&' . $token[1];
        }
        else if ('(' === $token[0]) ++$this->bracket;
        else if (')' === $token[0]) --$this->bracket;

        $this->signature .= $token[1];

        $this->bracket <= 0 && $this->unregister(__FUNCTION__);
    }

    protected function tagClassClose(&$token)
    {
        $this->unregister($this->callbacks);

        if ('' !== $this->signature)
        {
            $n = PHP_VERSION_ID < 50300 ? strtr($this->class->nsName, '\\', '_') : $this->class->nsName;

            $token[1] = 'function ' . $this->signature . '{'
                . 'if(' . count($this->arguments) . '<func_num_args()){'
                .   '${""}=array(' . implode(',', $this->arguments) . ')+func_get_args();'
                .   'call_user_func_array(array("' . $n . '","__construct"),${""});'
                . '}else{'
                .   $n . '::__construct(' . str_replace('&', '', implode(',', $this->arguments)) . ');'
                . '}}'
                . $token[1];

            $this->bracket   = 0;
            $this->signature = '';
            $this->arguments = array();
        }

        $this->callbacks = array('tagClassOpen' => T_SCOPE_OPEN);
        $this->register($this->callbacks);
    }
}

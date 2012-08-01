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
 * DestructorCatcher encapsulates class destructors inside a try/catch that avoids any
 * "Exception thrown without a stack frame in Unknown on line 0" cryptic error message.
 */
class Patchwork_PHP_Parser_DestructorCatcher extends Patchwork_PHP_Parser
{
    protected

    $callbacks = array('tagDestruct' => T_NAME_FUNCTION),

    $scope,
    $dependencies = array('ScopeInfo' => 'scope');


    protected function tagDestruct(&$token)
    {
        if ((T_CLASS === $this->scope->type || T_TRAIT === $this->scope->type) && 0 === strcasecmp($token[1], '__destruct'))
        {
            $this->register(array('tagDestructOpen' => T_SCOPE_OPEN));
        }
    }

    protected function tagDestructOpen(&$token)
    {
        $this->unregister(array(__FUNCTION__ => T_SCOPE_OPEN));
        $token[1] .= 'try{';
        $this->register(array('tagDestructClose' => T_BRACKET_CLOSE));
    }

    protected function tagDestructClose(&$token)
    {
        $this->unregister(array(__FUNCTION__ => T_BRACKET_CLOSE));
        $t = PHP_VERSION_ID >= 50306 ? (PHP_VERSION_ID >= 50400 ? '2,2' : '2') : '0';
        $token[1] = '}catch(' .( PHP_VERSION_ID >= 50300 ? '\\' : '' ). 'Exception $e)'
            . '{'
                . 'if(empty($e->__destructorException))'
                . '{'
                    . '$e=array($e,array_slice($e->getTrace(),-1));'
                    . '$e[0]->__destructorException=isset($e[1][0]["line"])||!isset($e[1][0]["class"])||strcasecmp("__destruct",$e[1][0]["function"])?1:$e[1][0]["class"];'
                    . '$e=$e[0];'
                . '}'
                . 'if(isset($e->__destructorException)&&__CLASS__===$e->__destructorException&&1===count(debug_backtrace(' . $t . ')))'
                . '{'
                    . '$e=array($e,set_exception_handler("var_dump"));'
                    . 'restore_exception_handler();'
                    . 'null!==$e[1]&&call_user_func($e[1],$e=$e[0])+exit(255);'
                . '}'
                . 'throw $e;'
            . '}' . $token[1];
    }
}

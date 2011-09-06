<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/lgpl.txt GNU/LGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Lesser General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class Patchwork_PHP_Parser_DestructorCatcher extends Patchwork_PHP_Parser
{
    protected

    $callbacks = array('tagDestruct' => T_NAME_FUNCTION),
    $dependencies = array('ScopeInfo' => 'scope');


    protected function tagDestruct(&$token)
    {
        if (T_CLASS === $this->scope->type && 0 === strcasecmp($token[1], '__destruct'))
        {
            $this->register(array('tagDestructOpen' => T_SCOPE_OPEN));
        }
    }

    protected function tagDestructOpen(&$token)
    {
        $this->unregister(array('tagDestructOpen' => T_SCOPE_OPEN));
        $token[1] .= 'try{';
        $this->register(array('tagDestructClose' => T_SCOPE_CLOSE));
    }

    protected function tagDestructClose(&$token)
    {
        $this->unregister(array('tagDestructClose' => T_SCOPE_CLOSE));

        $t = defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 50306 ? (PHP_VERSION_ID >= 50400 ? '2,2' : '2') : '0';

        $token[1] = '}catch(' .( T_NS_SEPARATOR > 0 ? '\\' : '' ). 'Exception $e)'
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

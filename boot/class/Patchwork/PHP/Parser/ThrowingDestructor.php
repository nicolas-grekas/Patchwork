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


class Patchwork_PHP_Parser_ThrowingDestructor extends Patchwork_PHP_Parser
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
        $this->register(array(
            'tagDestructThrow' => T_THROW,
            'tagDestructClose' => T_SCOPE_CLOSE,
        ));
    }

    protected function tagDestructThrow(&$token)
    {
        // Should this message be hidden when throw is inside a try/catch?
        $this->setError("Throwing exceptions inside destructors is strongly discouraged", E_USER_WARNING);
    }

    protected function tagDestructClose(&$token)
    {
        $this->unregister(array(
            'tagDestructThrow' => T_THROW,
            'tagDestructClose' => T_SCOPE_CLOSE,
        ));

        $token[1] = '}catch(' .( T_NS_SEPARATOR > 0 ? '\\' : '' ). 'Exception $e){'
            . 'if((E_WARNING|E_USER_WARNING)&error_reporting()){'
            . 'user_error("Throwing destructors should be avoided, please catch your exceptions",E_USER_WARNING);'
            . '$e=array($e,set_exception_handler("var_dump"));'
            . 'restore_exception_handler();'
            . 'if(null!==$e[1])call_user_func($e[1],$e[0]);'
            . '}throw $e[0];}' . $token[1];
    }
}

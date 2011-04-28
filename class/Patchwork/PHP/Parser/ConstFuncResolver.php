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

// TODO: parse for inline consts, functions and define()

class Patchwork_PHP_Parser_ConstFuncResolver extends Patchwork_PHP_Parser
{
    protected

    $openTag,
    $nsLoadCode = false,
    $callbacks = array('tagOpenTag' => T_SCOPE_OPEN),
    $dependencies = array('NamespaceInfo' => 'namespace', 'Scoper' => 'scope');


    protected function tagOpenTag(&$token)
    {
        if (T_NAMESPACE === $this->scope->type && $this->namespace)
        {
            $this->openTag =& $token;
            $this->register($this->callbacks = array(
                'tagFunction'   => T_USE_FUNCTION,
                'tagConstant'   => T_USE_CONSTANT,
                'tagScopeClose' => T_SCOPE_CLOSE,
            ));
        }
    }

    protected function tagFunction(&$token)
    {
        return T_NS_SEPARATOR !== $this->lastType ? $this->resolveConstFunc($token, 'function_exists') : null;
    }

    protected function tagConstant(&$token)
    {
        return T_NS_SEPARATOR !== $this->lastType ? $this->resolveConstFunc($token, 'defined') : null;
    }

    protected function resolveConstFunc(&$token, $exists)
    {
        $this->unshiftTokens(array(T_NS_SEPARATOR, '\\'), $token);

        if (  !$exists($token[1])
            || $exists($this->namespace . $token[1])
            || self::nsLoad(substr($this->namespace, 0, -1))
            || $exists($this->namespace . $token[1])  )
        {
            $this->nsLoadCode = self::nsLoadCode(substr($this->namespace, 0, -1));
            $this->unshiftTokens(array(T_NAMESPACE, 'namespace'));
        }

        return false;
    }

    protected function tagScopeClose(&$token)
    {
        $this->unregister();

        if (false !== $this->nsLoadCode)
        {
            $this->openTag[1] .= $this->nsLoadCode . ';';
            $this->nsLoadCode = false;
        }
    }


    static protected function nsLoad($ns)
    {
        //class_exists($ns, true);
        return false;
    }

    static protected function nsLoadCode($ns)
    {
        //return "class_exists('{$ns}', true)";
        return false;
    }
}

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


class Patchwork_PHP_Parser_ConstructorStatic extends Patchwork_PHP_Parser
{
    protected

    $tag = "\x9D",
    $construct,
    $destruct,
    $callbacks = array('tagClassOpen' => T_SCOPE_OPEN),
    $dependencies = array('ClassInfo' => array('class', 'scope'));


    protected function tagClassOpen(&$token)
    {
        if (T_CLASS === $this->scope->type)
        {
            $this->unregister();
            $this->construct = $this->destruct = (int) empty($this->class->extendsSelf);
            $this->register(array(
                'tagFunction'   => T_FUNCTION,
                'tagClassClose' => T_SCOPE_CLOSE,
            ));
        }
    }

    protected function tagFunction(&$token)
    {
        if (T_CLASS === $this->scope->type)
        {
            $t = $this->getNextToken($i);
            if ('&' === $t[0]) $t = $this->getNextToken($i);

            if (T_STRING === $t[0]) switch (strtolower($t[1]))
            {
                case '__constructstatic': $this->construct = 2; break;
                case '__destructstatic' : $this->destruct  = 2; break;
            }
        }
    }

    protected function tagClassClose(&$token)
    {
        $this->unregister(array('tagFunction' => T_FUNCTION));
        $this->register();

        $class = strtolower(strtr($this->class->nsName, '\\', '_'));

        $this->construct && $token[1] = "const c{$this->tag}=" . (2 === $this->construct ? "'{$class}';" : "'';static function __constructStatic(){}") . $token[1];
        $this->destruct  && $token[1] = "const d{$this->tag}=" . (2 === $this->destruct  ? "'{$class}';" : "'';static function __destructStatic() {}") . $token[1];

        if (isset($this->class->isTop) && false === $this->class->isTop) return;

        if ($this->class->extends)
        {
            1 !== $this->construct && $token[1] .= "if('{$class}'==={$this->class->name}::c{$this->tag}){$this->class->name}::__constructStatic();";
            1 !== $this->destruct  && $token[1] .= "if('{$class}'==={$this->class->name}::d{$this->tag})\$GLOBALS['_patchwork_destruct'][]='{$class}';";
        }
        else
        {
            2 === $this->construct && $token[1] .= "{$this->class->name}::__constructStatic();";
            2 === $this->destruct  && $token[1] .= "\$GLOBALS['_patchwork_destruct'][]='{$class}';";
        }
    }
}

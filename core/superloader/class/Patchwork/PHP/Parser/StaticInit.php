<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class Patchwork_PHP_Parser_StaticInit extends Patchwork_PHP_Parser
{
    protected

    $tag = "\x9D",
    $init,
    $free,
    $callbacks = array('tagClassOpen' => T_SCOPE_OPEN),

    $class, $scope,
    $dependencies = array('ClassInfo' => array('class', 'scope'));


    protected function tagClassOpen(&$token)
    {
        if (T_CLASS === $this->scope->type)
        {
            $this->unregister($this->callbacks);
            $this->init = $this->free = (int) empty($this->class->extendsSelf);
            $this->register(array(
                'tagFunction'   => T_FUNCTION,
                'tagClassClose' => T_BRACKET_CLOSE,
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
                case '__init': $this->init = 2; break;
                case '__free': $this->free = 2; break;
            }
        }
    }

    protected function tagClassClose(&$token)
    {
        $this->unregister(array('tagFunction' => T_FUNCTION, 'tagClassClose' => T_BRACKET_CLOSE));
        $this->register($this->callbacks);

        $class = strtolower(strtr($this->class->nsName, '\\', '_'));
        $d = "\\Patchwork_ShutdownHandler::\$destructors[]='{$class}';";
        PHP_VERSION_ID < 50300 && $d[0] = ' ';

        $this->init && $token[1] = "const i{$this->tag}=" . (2 === $this->init ? "'{$class}';" : "'';static function __init(){}") . $token[1];
        $this->free && $token[1] = "const f{$this->tag}=" . (2 === $this->free ? "'{$class}';" : "'';static function __free(){}") . $token[1];

        if (isset($this->class->isTop) && false === $this->class->isTop) return;

        if ($this->class->extends)
        {
            1 !== $this->init && $token[1] .= "if('{$class}'==={$this->class->name}::i{$this->tag}){$this->class->name}::__init();";
            1 !== $this->free && $token[1] .= "if('{$class}'==={$this->class->name}::f{$this->tag}){$d}";
        }
        else
        {
            2 === $this->init&& $token[1] .= "{$this->class->name}::__init();";
            2 === $this->free&& $token[1] .= $d;
        }
    }
}

<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2013 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork\PHP\Parser;

use Patchwork\PHP\Parser;

/**
 * ClassScalarInliner inlines scalar ::class name resolution
 */
class ClassScalarInliner extends Parser
{
    protected

    $callbacks = array('tagClass' => T_CLASS),
    $class, $nsResolved, $scope,
    $dependencies = array('ClassInfo' => array('class', 'nsResolved', 'scope'));


    protected function tagClass(&$token)
    {
        if (T_DOUBLE_COLON !== $this->prevType) return;
        if (T_STRING !== $this->penuType && T_STATIC !== $this->penuType) return;

        $types =& $this->types;
        end($types);  // T_DOUBLE_COLON
        prev($types); // T_STRING or T_STATIC

        switch ($t = strtolower($this->texts[key($types)]))
        {
        case 'parent': case 'self': case 'static':
            if (!$this->class || T_NS_SEPARATOR === prev($types)) return $this->unshiftTokens(array(T_STRING, $t));
            else if ('self' === $t) $class = $this->class->nsName;
            else if (T_FUNCTION !== $this->scope->type)
            {
                $this->setError("{$t}::class cannot be used for compile-time class name resolution", E_USER_ERROR);
                return;
            }
            else if ('static' === $t) $class = $this->unshiftCode('get_called_class()');
            else if (T_TRAIT === $this->class->type) $class = $this->unshiftCode('(get_parent_class()?:parent::self)');
            else if ($this->class->extends) $class = $this->class->extends;
            else return $this->unshiftTokens(array(T_STRING, $t));
        }

        if (isset($class)) {}
        else if (!isset($this->nsResolved[1])) return;
        else if ('\\' !== $this->nsResolved[0])
        {
            $this->setError("Unresolved namespaced identifier ({$this->nsResolved})", E_USER_WARNING);
            return;
        }
        else $class = substr($this->nsResolved, 1);

        if (false !== $class) $this->unshiftTokens(array(T_CONSTANT_ENCAPSED_STRING, "'{$class}'"));

        $t = 0;
        end($types);

        while (null !== $i = key($types)) switch ($types[$i])
        {
            default: if ($t >= 2) break 2;
            case T_STRING: case T_NS_SEPARATOR: case T_NAMESPACE:
                $this->texts[$i] = '';
                unset($types[$i]);
                end($types);
                ++$t;
        }

        $this->prevType = end($types);
        $this->penuType = prev($types);

        return false;
    }
}

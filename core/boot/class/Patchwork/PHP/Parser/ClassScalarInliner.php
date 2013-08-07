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

    $callbacks  = array('tagClass' => T_CLASS),
    $class, $nsResolved, $scope,
    $dependencies = array('ClassInfo' => array('class', 'nsResolved', 'scope'));


    protected function tagClass(&$token)
    {
        if (T_DOUBLE_COLON !== $this->prevType) return;

        $t =& $this->types;
        end($t);

        switch (0)
        {
        case T_STATIC - $this->penuType:
            if (!$this->class) $error = 'Cannot access static::class when no class scope is active';
            else if (T_FUNCTION !== $this->scope->type) $error = 'static::class cannot be used for compile-time class name resolution';
            else
            {
                $i = key($t);
                prev($t);
                if (T_NS_SEPARATOR === prev($t)) return;
                $this->texts[$i] = '';
                unset($t[$i]);
                end($t);
                $i = key($t);
                $this->texts[$i] = '';
                unset($t[$i]);

                $this->prevType = end($t);
                $this->penuType = prev($t);

                return $this->unshiftTokens(array(T_STRING, 'get_called_class'), '(', ')');
            }
            break;

        case strcasecmp('\self', $this->nsResolved):
            if (!$this->class) $error = 'Cannot access self::class when no class scope is active';
            else $class = $this->class->nsName;
            break;

        case strcasecmp('\parent', $this->nsResolved):
            if (!$this->class) $error = 'Cannot access parent::class when no class scope is active';
            else if (T_FUNCTION !== $this->scope->type) $error = 'parent::class cannot be used for compile-time class name resolution';
            else if (!$this->class->extends) $error = 'Cannot access parent:: when current class scope has no parent';
            else $class = $this->class->extends;
            break;

        default:
            if (!isset($this->nsResolved[0])) return;
            if ('\\' !== $this->nsResolved[0])
            {
                $this->setError("Unresolved namespaced identifier ({$this->nsResolved})", E_USER_WARNING);
                return;
            }

            $class = substr($this->nsResolved, 1);
        }

        if (isset($error))
        {
            $this->setError($error, E_USER_ERROR);
            return;
        }

        $this->unshiftTokens(array(T_CONSTANT_ENCAPSED_STRING, "'{$class}'"));

        while (null !== $i = key($t)) switch ($t[$i])
        {
            default: break 2;
            case T_DOUBLE_COLON:
                if (isset($class)) unset($class); // No break;
                else break 2;
            case T_STRING: case T_NS_SEPARATOR: case T_NAMESPACE:
                $this->texts[$i] = '';
                unset($t[$i]);
                end($t);
        }

        $this->prevType = end($t);
        $this->penuType = prev($t);

        return false;
    }
}

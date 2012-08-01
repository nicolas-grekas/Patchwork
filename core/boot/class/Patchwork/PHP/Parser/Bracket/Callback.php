<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

// FIXME: handle when $callbackIndex <= 0
// FIXME: unlike static callbacks, an overrider can not use its overriden function
//        through a dynamic callback, because that would lead to unwanted recursion.

/**
 * The Bracket_Callback parser participates in catching callbacks for at runtime function overriding.
 */
class Patchwork_PHP_Parser_Bracket_Callback extends Patchwork_PHP_Parser_Bracket
{
    protected

    $callbackIndex,
    $lead = 'patchwork_override_resolve(',
    $tail = ')',
    $nextTail = '',
    $overrides = array(),

    $scope, $class,
    $dependencies = array(
        'ConstantInliner' => 'scope',
        'ClassInfo' => 'class',
    );


    function __construct(Patchwork_PHP_Parser $parent, $callbackIndex, $overrides = array())
    {
        if (0 < $callbackIndex)
        {
            $this->overrides = $overrides;
            $this->callbackIndex = $callbackIndex - 1;
            parent::__construct($parent);
        }
    }

    protected function onOpen(&$token)
    {
        if (0 === $this->callbackIndex) $this->addLead($token[1]);
    }

    protected function onReposition(&$token)
    {
        if ($this->bracketIndex === $this->callbackIndex    ) $this->addLead($token[1]);
        if ($this->bracketIndex === $this->callbackIndex + 1) $this->addTail($token[1]);
    }

    protected function onClose(&$token)
    {
        if ($this->bracketIndex === $this->callbackIndex) $this->addTail($token[1]);
    }

    protected function addLead(&$token)
    {
        $t =& $this->getNextToken($a);

        // TODO: optimize more cases with the ConstantExpression parser

        if (T_CONSTANT_ENCAPSED_STRING === $t[0])
        {
            $a = $this->getNextToken($a);

            if (',' === $a[0] || ')' === $a[0])
            {
                $a = strtolower(substr($t[1], 1, -1));

                if (isset($this->overrides[$a]))
                {
                    $a = $this->overrides[$a];
                    $a = explode('::', $a, 2);

                    if (1 === count($a))
                    {
                        if ($this->class || strcasecmp($a[0], $this->scope->funcC)) $t[1] = "'{$a[0]}'";
                    }
                    else if (empty($this->class->nsName) || strcasecmp(strtr($a[0], '\\', '_'), strtr($this->class->nsName, '\\', '_')))
                    {
                        $t = ')';
                        $this->unshiftTokens(
                            array(T_ARRAY, 'array'), '(',
                            array(T_CONSTANT_ENCAPSED_STRING, "'{$a[0]}'"), ',',
                            array(T_CONSTANT_ENCAPSED_STRING, "'{$a[1]}'")
                        );
                    }
                }

                return;
            }
        }
        else if (T_FUNCTION === $t[0])
        {
            return; // Closure
        }
        else if (T_ARRAY === $t[0])
        {
            $a = $this->index;
            $t =& $this->tokens;
            $b = 0;

            if (PHP_VERSION_ID >= 50300)
            {
                // TODO: replace 'self' by __CLASS__ and in PHP 5.2, optimize
                // __CLASS__ and A\B by underscore resolved version, check for $this.

                while (isset($t[$a])) switch ($t[$a++][0])
                {
                case '(': ++$b; break;
                case ')':
                    if (0 >= --$b)
                    {
                        $c = $this->getNextToken($a);
                        if (0 > $b || ',' === $c[0] || ')' === $c[0]) return;
                        break;
                    }
                }
            }
        }
        else if (')' === $t[0]) return;
        else if (',' === $t[0]) return;

        $token .= $this->lead;
        $this->nextTail = $this->tail;
    }

    protected function addTail(&$token)
    {
        $token = $this->nextTail . $token;
        $this->nextTail = '';
    }
}

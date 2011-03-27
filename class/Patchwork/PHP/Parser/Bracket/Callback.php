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


// FIXME: handle when $callbackIndex <= 0

class Patchwork_PHP_Parser_Bracket_Callback extends Patchwork_PHP_Parser_Bracket
{
    protected

    $callbackIndex,
    $lead = 'patchwork_alias_resolve(',
    $tail = ')',
    $nextTail = '',
    $alias = array();


    function __construct(Patchwork_PHP_Parser $parent, $callbackIndex, $alias = array())
    {
        if (0 < $callbackIndex)
        {
            $this->alias = $alias;
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

        if (T_CONSTANT_ENCAPSED_STRING === $t[0])
        {
            $a = $this->getNextToken($a);

            if (',' === $a[0] || ')' === $a[0])
            {
                $a = strtolower(substr($t[1], 1, -1));

                if (isset($this->alias[$a]))
                {
                    $a = $this->alias[$a];
                    $a = explode('::', $a, 2);

                    if (1 === count($a)) $t[1] = "'{$a[0]}'";
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

        $token .= $this->lead;
        $this->nextTail = $this->tail;
    }

    protected function addTail(&$token)
    {
        $token = $this->nextTail . $token;
        $this->nextTail = '';
    }
}

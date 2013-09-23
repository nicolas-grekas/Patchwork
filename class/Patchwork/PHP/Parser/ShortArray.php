<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork\PHP\Parser;

use Patchwork\PHP\Parser;

/**
 * The ShortArray parser backports the short array syntax introduced in PHP 5.4.
 */
class ShortArray extends Parser
{
    public

    $targetPhpVersionId = -50400;

    protected

    $callbacks = array('openBracket' => '['),
    $dependencies = 'BracketWatcher';


    protected function openBracket(&$token)
    {
        switch ($this->prevType)
        {
        case '}':
            $t =& $this->types;
            end($t);
            while ('}' === current($t)) prev($t);
            switch (current($t)) {case ';': case '{': break 2;}

        case ')': case ']': case T_VARIABLE: case T_STRING:
            return;
        }

        $token[1] = 'array(';
        $this->register(array('~closeBracket' => T_BRACKET_CLOSE));
    }

    protected function closeBracket(&$token)
    {
        $token[1] = ')';
    }
}

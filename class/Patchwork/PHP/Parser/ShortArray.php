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
 * The ShortArray parser transforms to/from short array syntax depending on target PHP version.
 */
class ShortArray extends Parser
{
    protected

    $backwardCallbacks = array('tagOpenSquare' => '['),
    $forwardCallbacks = array('tagArray' => T_ARRAY),
    $dependencies = 'BracketWatcher';


    function __construct(parent $parent)
    {
        $this->callbacks = $parent->targetPhpVersionId < 50400
            ? $this->backwardCallbacks
            : $this->forwardCallbacks;

        parent::__construct($parent);
    }

    protected function tagOpenSquare(&$token)
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
        $this->register(array('~tagCloseSquare' => T_BRACKET_CLOSE));
    }

    protected function tagCloseSquare(&$token)
    {
        $token[1] = ')';
    }

    protected function tagArray(&$token)
    {
        $this->register('tagOpenRounded');
    }

    protected function tagOpenRounded(&$token)
    {
        $this->unregister(__FUNCTION__);

        if ('(' === $token[0])
        {
            $token[1] = '[';
            end($this->types);
            $this->texts[key($this->types)] = '';
            $this->register(array('~tagCloseRounded' => T_BRACKET_CLOSE));
        }
    }

    protected function tagCloseRounded(&$token)
    {
        $token[1] = ']';
    }
}

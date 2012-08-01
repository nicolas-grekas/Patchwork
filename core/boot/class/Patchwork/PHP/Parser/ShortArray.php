<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

/**
 * The ShortArray parser backports the short array syntax introduced in PHP 5.4.
 */
class Patchwork_PHP_Parser_ShortArray extends Patchwork_PHP_Parser
{
    protected

    $callbacks = array('openBracket' => '['),
    $dependencies = 'BracketWatcher';


    function __construct(parent $parent)
    {
        if (PHP_VERSION_ID >= 50400) $this->callbacks = array();
        parent::__construct($parent);
    }

    protected function openBracket(&$token)
    {
        switch ($this->prevType)
        {
        case '}':
            $token =& $this->types;
            end($token);
            while ('}' === current($token)) prev($token);
            switch (current($token)) {case ';': case '{': break 2;}

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

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
 * The PatchworkPath parser adds the current superpositioning level to patchworkPath()'s third parameter.
 */
class Patchwork_PHP_Parser_Bracket_PatchworkPath extends Patchwork_PHP_Parser_Bracket
{
    protected $level;

    function __construct(Patchwork_PHP_Parser $parent, $level)
    {
        $this->level = (string) (int) $level;
        parent::__construct($parent);
    }

    protected function onClose(&$token)
    {
        2 === $this->bracketIndex && $token[1] = ',' . $this->level . $token[1];
    }
}

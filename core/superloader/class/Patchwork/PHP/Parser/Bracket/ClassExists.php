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
 * The ClassExists parser force class_exists' second $autoload parameter to true.
 */
class Patchwork_PHP_Parser_Bracket_ClassExists extends Patchwork_PHP_Parser_Bracket
{
    protected $tail = "||\\Patchwork_Superloader::exists(\$\x9D,0)";

    protected function onOpen(&$token)
    {
        $token[1] .= "\$\x9D=(";
        PHP_VERSION_ID < 50300 && $this->tail[2] = ' ';
    }

    protected function onReposition(&$token)
    {
        if (1 === $this->bracketIndex) $token[1] = ')' . $token[1] . '(';
        if (2 === $this->bracketIndex) $token[1] = ')' . $this->tail . $token[1];
    }

    protected function onClose(&$token)
    {
        if (0 === $this->bracketIndex) $token[1] = ')' . $token[1];
        if (1 === $this->bracketIndex) $token[1] = ')' . $this->tail . $token[1];
    }
}

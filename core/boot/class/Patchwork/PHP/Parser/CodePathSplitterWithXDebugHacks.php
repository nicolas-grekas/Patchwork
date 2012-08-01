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
 * The CodePathSplitterWithXDebugHacks adds workarounds for Xdebug's code coverage misses.
 */
class Patchwork_PHP_Parser_CodePathSplitterWithXDebugHacks extends Patchwork_PHP_Parser_CodePathSplitter
{
    protected $serviceName = 'Patchwork_PHP_Parser_CodePathSplitter';


    protected function isCodePathNode(&$token)
    {
        static $skip = 0;

        if (':' === $this->prevType) $end = end($this->structStack);

        $r = parent::isCodePathNode($token);

        if ($skip)
        {
            $r = 1 === $skip-- ? self::BRANCH_CONTINUE : self::BRANCH_OPEN;
        }
        else if (isset($end) && '?' === $end && self::BRANCH_OPEN === $r)
        {
            $r = self::BRANCH_CONTINUE;
        }
        else if ('?' === $this->prevType)
        {
            end($this->types);
            $this->texts[key($this->types)] .= ':';
            $token[1] = '0?' . $token[1];
            $r = self::BRANCH_OPEN;
        }
        else if ('?' === $token[0])
        {
            $t = $this->getNextToken();
            if (':' !== $t[0])
            {
                $token[1] .= '(';
                $this->unshiftTokens(array('@', '1?1:1):('), array('@', '0?0:0)'), array('@', '?'));
                $skip = 3;
            }
        }
        else if ('}' === $token[0] && ';' === $this->prevType && '{' === $this->penuType)
        {
            end($this->types);
            if (';' === $this->texts[key($this->types)]) $this->texts[key($this->types)] = '(0?0:0);';
        }

        return $r;
    }
}

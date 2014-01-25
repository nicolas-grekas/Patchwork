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
 * The CodePathSplitterWithXDebugHacks adds workarounds for Xdebug's code coverage misses.
 */
class CodePathSplitterWithXDebugHacks extends CodePathSplitter
{
    protected

    $skip = 0,
    $serviceName = 'Patchwork\PHP\Parser\CodePathSplitter';


    protected function getNodeType(&$token)
    {
        if (':' === $this->prevType) $end = end($this->structStack);

        $c = parent::getNodeType($token);

        if ($this->skip)
        {
            $c = 1 === $this->skip-- ? self::BRANCH_RESUME : self::BRANCH_OPEN;
        }
        else if (isset($end) && '?' === $end && self::BRANCH_OPEN === $c)
        {
            $c = self::BRANCH_RESUME;
        }
        else if ('?' === $this->prevType)
        {
            end($this->types);
            $this->texts[key($this->types)] .= ':';
            $token[1] = '0?' . $token[1];
            $c = self::BRANCH_OPEN;
        }
        else if ('?' === $token[0])
        {
            $t = $this->getNextToken();
            if (':' !== $t[0])
            {
                $token[1] .= '(';
                $this->unshiftTokens(array('@', '!!1):('), array('@', '!!0)'), array('@', '?'));
                $this->skip = 3;
            }
        }
        else if ('}' === $token[0] && ';' === $this->prevType && '{' === $this->penuType)
        {
            end($this->types);
            if (';' === $this->texts[key($this->types)]) $this->texts[key($this->types)] = '(!!0);';
        }

        return $c;
    }
}

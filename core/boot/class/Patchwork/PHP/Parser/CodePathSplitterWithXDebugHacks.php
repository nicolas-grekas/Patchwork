<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2012 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/lgpl.txt GNU/LGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Lesser General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/

/**
 * The CodePathSplitterWithXDebugHacks adds workarounds for Xdebug's code coverage misses.
 */
class Patchwork_PHP_Parser_CodePathSplitterWithXDebugHacks extends Patchwork_PHP_Parser_CodePathSplitter
{
    protected $serviceName = 'Patchwork_PHP_Parser_CodePathSplitter';


    protected function isCodePathNode(&$token)
    {
        if (':' === $this->prevType) $end = end($this->structStack);

        $r = parent::isCodePathNode($token);

        if (isset($end) && '?' === $end && self::CODE_PATH_OPEN === $r)
        {
            $r = self::CODE_PATH_CONTINUE;
        }
        else if ('?' === $this->prevType)
        {
            end($this->types);
            if (':' === $token[0])
            {
                $this->texts[key($this->types)] .= ':';
                $token[1] = '0?' . $token[1];
                $r = self::CODE_PATH_OPEN;
            }
            else
            {
                $this->texts[key($this->types)] .= '(';
                $token[1] = "1?1:1):(\n\t\t0?0:0)\n\t?" . $token[1];
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

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
    protected

    $closeCurlyOnSemicolon,
    $prevControlText;


    protected function isCodePathNode(&$token)
    {
        if (')' === $this->prevType)
        {
            if ('{' !== $token[0] && ':' !== $token[0] && T_IF === end($this->stack))
            {
                if (!isset($token[0][0])) switch ($token[0])
                {
                case T_IF:
                case T_SWITCH:
                case T_DO:
                case T_WHILE:
                case T_FOR:
                case T_FOREACH:
                    array_pop($this->stack);
                    $this->prevControlText = $token[1];
                    return false;
                }

                end($this->types);
                $this->texts[key($this->types)] .= "{";
                $this->closeCurlyOnSemicolon = true;
            }
        }
        else if (';' === $this->prevType || ':' === $this->prevType) $end = end($this->stack);

        $r = parent::isCodePathNode($token);

        if (isset($end))
        {
            if (isset($this->closeCurlyOnSemicolon) && ';' === $end  && ';' === $this->prevType)
            {
                end($this->types);
                $this->texts[key($this->types)] .= '}';
                $this->closeCurlyOnSemicolon = null;
            }
            else if ('?' === $end && ':' === $this->prevType && self::CODE_PATH_OPEN === $r)
            {
                $r = false;
            }
        }
        else if ('?' === $this->prevType)
        {
            end($this->types);
            if (':' === $token[0])
            {
                $this->texts[key($this->types)] .= ":";
                $token[1] = "0?" . $token[1];
                $r = self::CODE_PATH_OPEN;
            }
            else
            {
                $this->texts[key($this->types)] .= "(";
                $token[1] = "1?1:1):(\n\t\t0?0:0)\n\t?" . $token[1];
            }
        }
        else if (isset($this->prevControlText)) switch ($this->prevType)
        {
        case T_IF:
        case T_SWITCH:
        case T_DO:
        case T_WHILE:
        case T_FOR:
        case T_FOREACH:
            $token[1] = "/*{$this->prevControlText}*/" . $token[1];
            $this->prevControlText = null;
            $r = self::CODE_PATH_OPEN;
        }

        return $r;
    }
}

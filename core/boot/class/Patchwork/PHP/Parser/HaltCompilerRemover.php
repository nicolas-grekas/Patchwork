<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

class Patchwork_PHP_Parser_HaltCompilerRemover extends Patchwork_PHP_Parser
{
    // Cut data after __halt_compiler() while setting $compiler_halt_offset

    function removeHaltCompiler($code, &$compiler_halt_offset)
    {
        if (false === stripos($code, '__halt_compiler'))
        {
            $compiler_halt_offset = 0;
            return $code;
        }

        $data_offset = 0;

        foreach (parent::getTokens($code, false) as $t)
        {
            if (isset($t[1])) $data_offset += strlen($t[1]);
            else ++$data_offset;

            if (isset($tail))
            {
                if (isset($t[1])) switch ($t[0])
                {
                case T_WHITESPACE:
                case T_COMMENT:
                case T_DOC_COMMENT:
                case T_BAD_CHARACTER: continue 2;
                }

                if (0 === --$tail) break;
            }
            else if (T_HALT_COMPILER === $t[0])
            {
                $code_len = $data_offset - strlen($t[1]);
                $tail = 3;
            }
        }

        if (isset($code_len))
        {
            $compiler_halt_offset += $data_offset;
            $code = substr($code, 0, $code_len);
        }
        else $compiler_halt_offset = 0;

        return $code;
    }
}

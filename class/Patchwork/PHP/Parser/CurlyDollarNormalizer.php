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
 * CurlyDollarNormalizer transforms "${var}" style string interpolation to "{$var}".
 *
 * "${var}" style is a lot harder to parse because of the T_STRING_VARNAME special token.
 * When this parser is enabled, other parsers don't have to manage that special case.
 */
class CurlyDollarNormalizer extends Parser
{
    protected

    $callbacks  = array('tagDollarCurly' => T_DOLLAR_OPEN_CURLY_BRACES),
    $dependencies = 'BracketWatcher';


    protected function tagDollarCurly(&$token)
    {
        $t =& $this->tokens;
        $i =  $this->index;

        if (! isset($t[$i], $t[$i+1])) return;

        if (T_STRING_VARNAME === $t[$i][0] && ('}' === $t[$i+1][0] || '[' === $t[$i+1][0]))
        {
            $t[$i] = array(T_VARIABLE, '$' . $t[$i][1]);
        }
        else
        {
            if (T_STRING_VARNAME === $t[$i][0]) // Seen before PHP 5.5
            {
                $t[$i][0] = T_STRING;
                if ('parent' === $k = strtolower($t[$i][1]) or 'self' === $k) $t[$i][1] = $k;
            }

            $this->unshiftTokens('$', '{');
            $this->register(array('~tagCurlyClose' => T_BRACKET_CLOSE));
        }

        return $this->unshiftTokens(array(T_CURLY_OPEN, '{'));
    }

    protected function tagCurlyClose(&$token)
    {
        $this->unshiftTokens('}');
    }
}

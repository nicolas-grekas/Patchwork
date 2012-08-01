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
 * CurlyDollarNormalizer transforms "${var}" style string interpolation to "{$var}".
 *
 * "${var}" style is a lot harder to parse because of the T_STRING_VARNAME special token.
 * When this parser is enabled, other parsers don't have to manage that special case.
 */
class Patchwork_PHP_Parser_CurlyDollarNormalizer extends Patchwork_PHP_Parser
{
    protected

    $callbacks  = array('tagDollarCurly' => T_DOLLAR_OPEN_CURLY_BRACES),
    $dependencies = 'BracketWatcher';


    protected function tagDollarCurly(&$token)
    {
        $t =& $this->tokens;
        $i =  $this->index;

        if (!isset($t[$i], $t[$i+1]) || T_STRING_VARNAME !== $t[$i][0]) return;

        if ('}' === $t[$i+1][0] || '[' === $t[$i+1][0])
        {
            $t[$i] = array(T_VARIABLE, '$' . $t[$i][1]);
        }
        else
        {
            $this->unshiftTokens('$', '{');
            $this->register(array('~tagCurlyClose' => T_BRACKET_CLOSE));
            $t[$i] = array(T_CONSTANT_ENCAPSED_STRING, "'{$t[$i][1]}'");
        }

        return $this->unshiftTokens(array(T_CURLY_OPEN, '{'));
    }

    protected function tagCurlyClose(&$token)
    {
        $this->unshiftTokens('}');
    }
}

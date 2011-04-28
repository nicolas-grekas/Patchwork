<?php /***** vi: set encoding=utf-8 expandtab shiftwidth=4: ****************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class Patchwork_PHP_Parser_ConstantExpression extends Patchwork_PHP_Parser
{
    protected $expressionValue;


    protected static $variableType = array(
        T_EVAL, T_LINE, T_FILE, T_DIR, T_FUNC_C, T_CLASS_C,
        T_METHOD_C, T_NS_C, T_INCLUDE, T_REQUIRE, T_GOTO,
        T_CURLY_OPEN, T_VARIABLE, '$', T_INCLUDE_ONCE,
        T_REQUIRE_ONCE, T_DOLLAR_OPEN_CURLY_BRACES, T_EXIT,
        T_COMMENT, T_DOC_COMMENT, T_FUNCTION,
    );


    function nextExpressionIsConstant()
    {
        $j = $this->index;
        $tokens =& $this->tokens;
        $const_code = array();
        $bracket = 0;
        $close = 0;

        while (isset($tokens[$j]))
        {
            switch ($tokens[$j][0])
            {
            case '`':
            case T_STRING:
            case T_NS_SEPARATOR:
                $close = 2;
                break;

            case '?': case '(': case '{': case '[':
                ++$bracket;
                break;

            case ':': case ')': case '}': case ']':
                $bracket-- || ++$close;
                break;

            case ',':
                $bracket   || ++$close;
                break;

            case T_AS:
            case T_CLOSE_TAG:
            case ';':
                ++$close;
                break;

            case T_WHITESPACE: break;

            default:
                if (in_array($tokens[$j][0], self::$variableType, true)) $close = 2;
            }

            if (1 === $close)
            {
                $const_code = implode('', $const_code);

                if (false !== @eval("\$close=({$const_code});"))
                {
                    $tokens[--$j] = array(
                        T_CONSTANT_ENCAPSED_STRING,
                        self::export($close)
                            . str_repeat("\n", substr_count($const_code, "\n"))
                    );

                    if ($j > $this->index)
                    {
                        $tokens = array_slice($tokens, $j - $this->index);
                        $this->index = 0;
                    }

                    $this->expressionValue = $close;

                    return true;
                }
                else return false;
            }
            else if (2 === $close)
            {
                return false;
            }
            else $const_code[] = isset($tokens[$j][1]) ? $tokens[$j][1] : $tokens[$j];

            ++$j;
        }
    }
}

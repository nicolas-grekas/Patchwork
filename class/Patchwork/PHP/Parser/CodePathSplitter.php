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
 * The CodePathSplitter parser merges and splits lines at code path nodes, enabling extensive code coverage analysis.
 *
 * TODO: more clever whitespace offsets with linePrefix
 * TODO: inline tags or other idea to mesure branch related coverage metrics
 */
class Patchwork_PHP_Parser_CodePathSplitter extends Patchwork_PHP_Parser
{
    const

    BRANCH_OPEN = 1,
    BRANCH_CONTINUE = 2;

    protected

    $lineHasSemantic = false,
    $linePrefix = '',
    $structStack = array(),
    $callbacks = array(
        '~tagSemantic' => T_SEMANTIC,
        '~tagNonSemantic' => T_NON_SEMANTIC,
    ),
    $dependencies = array(
        'ControlStructBracketer', // Curly braces around blocks are required for correct code coverage
        'CaseColonEnforcer', // Makes case statements easier to parse
    );


    protected function tagSemantic(&$token)
    {
        if ($this->isSpaceAllowed($token))
        {
            switch ($this->isCodePathNode($token))
            {
            case self::BRANCH_OPEN:
            case self::BRANCH_CONTINUE:
                if ($this->lineHasSemantic)
                {
                    $prefix = $this->linePrefix;
                }
            }
        }

        if (isset($token[0][0]))
        {
            $this->linePrefix .= str_repeat(' ', strlen($token[1]));
        }
        else switch ($token[0])
        {
            case T_CONSTANT_ENCAPSED_STRING:
            case T_ENCAPSED_AND_WHITESPACE:
            case T_OPEN_TAG_WITH_ECHO:
            case T_INLINE_HTML:
            case T_CLOSE_TAG:
            case T_OPEN_TAG:
                if (false !== $lf = strrpos($token[1], "\n"))
                {
                    $this->linePrefix = substr($token[1], $lf + 1);
                    $this->linePrefix = preg_replace('/[^\t ]/', ' ', utf8_decode($this->linePrefix));
                    break;
                }
                // No break;

            default:
                $this->linePrefix .= preg_replace('/[^\t ]/', ' ', utf8_decode($token[1]));
        }

        isset($prefix) and $token[1] = "\n" . $prefix . $token[1];

        $this->lineHasSemantic = true;
    }

    protected function isSpaceAllowed(&$token)
    {
        // Checks if a new line can be prepended to the current token

        if (isset($token[0][0])) switch ($token[0])
        {
        case '"':
        case '`':
        case '[':
            if ($this->inString & 1) return false;
            break;
        }
        else switch ($token[0])
        {
        case T_VARIABLE:
            if ($this->inString & 1) return false;
            break;

        case T_END_HEREDOC:
        case T_OPEN_TAG:
        case T_NUM_STRING:
        case T_STR_STRING:
        case T_CURLY_OPEN:
        case T_CURLY_CLOSE:
        case T_INLINE_HTML:
        case T_STRING_VARNAME:
        case T_OPEN_TAG_WITH_ECHO:
        case T_ENCAPSED_AND_WHITESPACE:
        case T_DOLLAR_OPEN_CURLY_BRACES:
            return false;
        }

        // Checks if a new line can be appended to the previous token

        if (isset($this->prevType[0])) switch ($this->prevType)
        {
        case ']':
            if ($this->inString & 1) return false;
            break;

        case '"':
        case '`':
            return false;
        }
        else switch ($this->prevType)
        {
        case T_VARIABLE:
            if ($this->inString & 1) return false;
            break;

        case T_END_HEREDOC:
        case T_CLOSE_TAG:
        case T_NUM_STRING:
        case T_STR_STRING:
        case T_CURLY_OPEN:
        case T_CURLY_CLOSE:
        case T_INLINE_HTML:
        case T_START_HEREDOC:
        case T_STRING_VARNAME:
        case T_ENCAPSED_AND_WHITESPACE:
        case T_DOLLAR_OPEN_CURLY_BRACES:
            return false;
        }

        return true;
    }

    protected function isCodePathNode(&$token)
    {
        $r = 0;
        $c = self::BRANCH_CONTINUE;
        $o = self::BRANCH_OPEN;

        // Checks if the previous token ends a code path

        if (isset($this->prevType[0])) switch ($this->prevType)
        {
        case '(':
            $this->structStack[] = isset($this->penuType[0]) ? -1 : $this->penuType;
            break;

        case '[':
            $this->structStack[] = '[';
            break;

        case '{':
            if (')' === $this->penuType)
            {
                if (T_ENDFOR === end($this->structStack)) array_pop($this->structStack);
                else if (T_DEFAULT === $token[0]) $r = $c;
                else if (T_CASE !== $token[0]) $r = $c = $o;
            }
            $this->structStack[] = ')' === $this->penuType || T_ELSE === $this->penuType || T_STRING === $this->penuType ? T_ELSE : '{';
            break;

        case '?':
            $this->structStack[] = '?';
            if (':' !== $token[0]) $r = $c = $o;
            break;

        case ':':
            switch (end($this->structStack))
            {
            case '?': $this->structStack[key($this->structStack)] = '-';
            case T_IF:
            case T_ELSEIF:
            case T_FOR:
            case T_FOREACH:
            case T_SWITCH:
            case T_WHILE:
                $c = $o;
            }
            $r = $c;
            break;

        case ')':
            switch (array_pop($this->structStack))
            {
            case T_EXIT:
                if (';' !== $token[0]) $r = $c;
                break;

            case T_IF:
            case T_ELSEIF:
            case T_WHILE:
            case T_FOR:
            case T_FOREACH:
                if (';' === end($this->structStack)) array_pop($this->structStack);
                if ('{' === $token[0]) break;
                if (':' !== $token[0]) $this->structStack[] = ';';
                $r = $c = $o;
                break;

            case T_ENDFOR:
                array_pop($this->structStack);
                $this->structStack[] = T_ENDFOR;
            }
            break;

        case ']':
            array_pop($this->structStack);
            break;

        case '}':
            if (T_ELSE === array_pop($this->structStack)) $r = $c;
            break;

        case ';':
            if (';' === $token[0] && T_FOR === end($this->structStack)) $this->structStack[] = T_ENDFOR;

            if (';' === end($this->structStack))
            {
                array_pop($this->structStack);
                $r = $c;
            }
            else if (!isset($this->penuType[0])) switch ($this->penuType)
            {
            case T_EXIT:
            case T_ENDIF:
            case T_ENDFOR:
            case T_ENDWHILE:
            case T_ENDSWITCH:
            case T_ENDFOREACH:
                $r = $c;
            }
            break;
        }
        else switch ($this->prevType)
        {
        case T_DO:
            $this->structStack[] = T_DO;
            break;

        case T_WHILE:
            if (T_DO === end($this->structStack))
            {
                array_pop($this->structStack);
                $this->prevType = T_DO;
            }
            break;

        case T_BOOLEAN_OR:
        case T_BOOLEAN_AND:
        case T_LOGICAL_OR:
        case T_LOGICAL_AND:
        case T_LOGICAL_XOR:
            if ('-' !== end($this->structStack)) $this->structStack[] = '-';
            $r = $c = $o;
            break;

        case T_GOTO:
        case T_BREAK:
        case T_CONTINUE:
        case T_RETURN:
        case T_THROW:
        case T_ELSE:
            if (T_ELSE !== $this->prevType || ('{' !== $token[0] && ':' !== $token[0])) $this->structStack[] = ';';
            break;
        }

        // Checks if the current token starts a new code path

        if (isset($token[0][0])) switch ($token[0])
        {
        case '?':
        case ':':
        case ',':
        case ']':
        case ')':
        case '}':
        case ';':
            if ('-' === end($this->structStack))
            {
                array_pop($this->structStack);
                $r = $c;
            }
            break;
        }
        else switch ($token[0])
        {
        case T_CATCH:
        case T_ELSE:
        case T_ELSEIF:
            $r = $c = $o;
            break;
        }

        return $r;
    }

    protected function tagNonSemantic(&$token)
    {
        if (false !== $lf = strrpos($token[1], "\n"))
        {
            $this->lineHasSemantic = false;
            $this->linePrefix = substr($token[1], $lf + 1);
        }
        else
        {
            $this->linePrefix .= $token[1];
        }
    }
}

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
 * The CodePathSplitter parser puts one token per line
 * and encodes code paths directly in the source,
 * enabling extensive code coverage analysis.
 */
class CodePathSplitter extends Parser
{
    const

    BRANCH_OPEN = 3,
    BRANCH_RESUME = 2,
    BRANCH_CONTINUE = 1;

    protected

    $nodeType = 0,
    $structStack = array(),
    $callbacks = array(
        'tagNode' => T_SEMANTIC,
        '~tagCommit' => T_SEMANTIC,
    ),
    $dependencies = array(
        'ControlStructBracketer', // Curly braces around blocks are required for correct code coverage
        'CaseColonEnforcer', // Makes case statements easier to parse
    );


    protected function tagNode(&$token)
    {
        if (! $this->nodeType && $this->isSpaceAllowed($token))
        {
            $this->nodeType = $this->getNodeType($token);
        }
    }

    protected function tagCommit(&$token)
    {
        end($this->texts);

        switch ($this->nodeType)
        {
        case self::BRANCH_OPEN:     $this->texts[key($this->texts)] .= "\r\r\r"; break;
        case self::BRANCH_RESUME:   $this->texts[key($this->texts)] .= "\r\r"; break;
        case self::BRANCH_CONTINUE: $this->texts[key($this->texts)] .= "\r"; break;
        }

        $this->nodeType = 0;
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

    protected function getNodeType(&$token)
    {
        $c = self::BRANCH_CONTINUE;
        $r = self::BRANCH_RESUME;
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
                else if (T_DEFAULT === $token[0]) $c = $r;
                else if (T_CASE !== $token[0]) $c = $r = $o;
            }
            $this->structStack[] = ')' === $this->penuType || T_ELSE === $this->penuType || T_STRING === $this->penuType ? T_ELSE : '{';
            break;

        case '?':
            $this->structStack[] = '?';
            if (':' !== $token[0]) $c = $r = $o;
            break;

        case ':':
            if (T_DEFAULT === $this->penuType) $r = $c;
            switch (end($this->structStack))
            {
            case '?': $this->structStack[key($this->structStack)] = '-';
            case T_IF:
            case T_ELSEIF:
            case T_FOR:
            case T_FOREACH:
            case T_SWITCH:
            case T_WHILE:
                $r = $o;
            }
            $c = $r;
            break;

        case ')':
            switch (array_pop($this->structStack))
            {
            case T_EXIT:
                if (';' !== $token[0]) $c = $r;
                break;

            case T_IF:
            case T_ELSEIF:
            case T_WHILE:
            case T_FOR:
            case T_FOREACH:
                if (';' === end($this->structStack)) array_pop($this->structStack);
                if ('{' === $token[0]) break;
                if (':' !== $token[0]) $this->structStack[] = ';';
                $c = $r = $o;
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
            if (T_ELSE === array_pop($this->structStack)) $c = $r;
            break;

        case ';':
            if (';' === $token[0] && T_FOR === end($this->structStack)) $this->structStack[] = T_ENDFOR;

            if (';' === end($this->structStack))
            {
                array_pop($this->structStack);
                $c = $r;
            }
            else if (!isset($this->penuType[0])) switch ($this->penuType)
            {
            case T_EXIT:
            case T_ENDIF:
            case T_ENDFOR:
            case T_ENDWHILE:
            case T_ENDSWITCH:
            case T_ENDFOREACH:
                $c = $r;
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
            $c = $r = $o;
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
                $c = $r;
            }
            break;
        }
        else switch ($token[0])
        {
        case T_CATCH:
        case T_ELSE:
        case T_ELSEIF:
            $c = $r = $o;
            break;
        }

        return $c;
    }
}

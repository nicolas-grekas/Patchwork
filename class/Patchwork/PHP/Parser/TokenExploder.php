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
 * The TokenExploder parser does one token per line.
 *
 * Default args and implicit code paths in ifs, loops and switch aren't reported.
 */
class Patchwork_PHP_Parser_TokenExploder extends Patchwork_PHP_Parser
{
    const

    CODE_PATH_OPEN = 1,
    CODE_PATH_CONTINUE = -1;

    protected

    $stack = array(),
    $callbacks = array(
        '~tagSemantic' => T_SEMANTIC,
        '~tagNonSemantic' => T_NON_SEMANTIC,
    );


    protected function tagSemantic(&$token)
    {
        if (T_INLINE_HTML === $token[0]) $this->tagNonSemantic($token);
        else if ($this->isSpaceAllowed($token))
        {
            $n = $this->isCodePathNode($token);
            if (self::CODE_PATH_CONTINUE === $n) $token[1] = "\n/*CONT*/" . $token[1];
            else if (self::CODE_PATH_OPEN === $n) $token[1] = "\n/*PATH*/" . $token[1];
//            else $token[1] = "\n" . $token[1];
        }
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

        case T_OPEN_TAG:
        case T_NUM_STRING:
        case T_STR_STRING:
        case T_CURLY_OPEN:
        case T_CURLY_CLOSE:
        case T_INLINE_HTML:
        case T_END_HEREDOC:
        case T_STRING_VARNAME:
        case T_OPEN_TAG_WITH_ECHO:
        case T_ENCAPSED_AND_WHITESPACE:
        case T_DOLLAR_OPEN_CURLY_BRACES:
            return false;
        }

        // Checks if a new line can be appended to the previous token

        if (isset($this->lastType[0])) switch ($this->lastType)
        {
        case ']':
            if ($this->inString & 1) return false;
            break;

        case '"':
        case '`':
            return false;
        }
        else switch ($this->lastType)
        {
        case T_VARIABLE:
            if ($this->inString & 1) return false;
            break;

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
        $default = false;
        $continue = self::CODE_PATH_CONTINUE;
        $open = self::CODE_PATH_OPEN;

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
            if ('-' === end($this->stack))
            {
                array_pop($this->stack);
                $default = $continue;
            }
            if (':' === $token[0] && '?' === end($this->stack))
            {
                $default = $continue = $open;
            }
            break;
        }
        else switch ($token[0])
        {
        case T_FUNCTION:
        case T_CATCH:
        case T_ELSE:
        case T_ELSEIF:
        case T_CASE:
        case T_DEFAULT:
            $default = $continue = $open;
            break;
        }

        // Checks if the previous token ends a code path

        if (isset($this->lastType[0])) switch ($this->lastType)
        {
        case '(':
            $this->stack[] = isset($this->penuType[0]) ? 0 : $this->penuType;
            break;

        case '[':
            $this->stack[] = '[';
            break;

        case '{':
            $this->stack[] = ')' === $this->penuType || T_ELSE === $this->penuType ? T_ELSE : '{';
            if (')' === $this->penuType) return $open;
            break;

        case '?':
            $this->stack[] = '?';
            return $open;

        case ':':
            if ('?' !== end($this->stack)) return $open;
            $this->stack[] = '-';
            break;

        case ')':
            switch (array_pop($this->stack))
            {
            case T_EXIT:
                if (';' === $token[0]) break;
                return $continue;

            case T_IF:
            case T_ELSEIF:
            case T_WHILE:
            case T_FOR:
            case T_FOREACH:
                if (';' === end($this->stack)) array_pop($this->stack);
                if ('{' === $token[0]) break;
                if (':' !== $token[0]) $this->stack[] = ';';
                return $open;
            }
            break;

        case ']':
            array_pop($this->stack);
            break;

        case '}':
            if (T_ELSE === array_pop($this->stack)) return $continue;
            break;

        case ';':
            if (';' === end($this->stack))
            {
                array_pop($this->stack);
                return $continue;
            }

            if (isset($this->penuType[0])) break;
            else switch ($this->penuType)
            {
            case T_EXIT:
            case T_ENDIF:
            case T_ENDFOR:
            case T_ENDWHILE:
            case T_ENDSWITCH:
            case T_ENDFOREACH:
                return $continue;
            }
        }
        else switch ($this->lastType)
        {
        case T_DO:
            $this->stack[] = T_DO;
            break;

        case T_WHILE:
            if (T_DO === end($this->stack))
            {
                array_pop($this->stack);
                $this->lastType = T_DO;
            }
            break;

        case T_BOOLEAN_OR:
        case T_BOOLEAN_AND:
        case T_LOGICAL_OR:
        case T_LOGICAL_AND:
        case T_LOGICAL_XOR:
            if ('-' !== end($this->stack)) $this->stack[] = '-';
            return $open;

        case T_GOTO:
        case T_BREAK:
        case T_CONTINUE:
        case T_RETURN:
        case T_THROW:
        case T_ELSE:
            if (T_ELSE !== $this->lastType || ('{' !== $token[0] && ':' !== $token[0])) $this->stack[] = ';';
            break;
        }

        return $default;
    }

    protected function tagNonSemantic(&$token)
    {
        if (' ' !== $token[1] && '' !== ltrim($token[1], " \t"))
            $token[1] = ('/' === $this->lastType ? ' ' : '') . "/*" . urlencode($token[1]) . '*/';
    }
}

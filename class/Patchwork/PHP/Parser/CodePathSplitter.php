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
 * The CodePathSplitter parser merges and splits lines at code path nodes.
 *
 * Default args and implicit code paths in ifs, loops and switch aren't handled.
 */
class Patchwork_PHP_Parser_CodePathSplitter extends Patchwork_PHP_Parser
{
    const

    CODE_PATH_OPEN = 1,
    CODE_PATH_CONTINUE = -1;

    protected

    $structStack = array(),
    $callbacks = array(
        '~tagSemantic' => T_SEMANTIC,
        '~tagNonSemantic' => T_NON_SEMANTIC,
    ),
    $dependencies = 'ControlStructBracketer'; // Curly braces around blocks are required for correct code coverage


    protected function tagSemantic(&$token)
    {
        if (T_INLINE_HTML === $token[0]) $this->tagNonSemantic($token);
        else if ($this->isSpaceAllowed($token))
        {
            $n = $this->isCodePathNode($token);
            if (self::CODE_PATH_CONTINUE === $n) $token[1] = "\n\t" . $token[1];
            else if (self::CODE_PATH_OPEN === $n) $token[1] = "\n\t\t" . $token[1];
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

        case T_END_HEREDOC:
            $token[1] .= "\n";
            // No break;
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
        $r = false;
        $c = self::CODE_PATH_CONTINUE;
        $o = self::CODE_PATH_OPEN;

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
                if (T_ENDFOR !== end($this->structStack)) $c = $o;
                else array_pop($this->structStack);
                $r = $c;
            }
            $this->structStack[] = ')' === $this->penuType || T_ELSE === $this->penuType || T_STRING === $this->penuType ? T_ELSE : '{';
            break;

        case '?':
            $this->structStack[] = '?';
            if (':' !== $token[0]) $r = $c = $o;
            break;

        case ':':
            if ('?' === end($this->structStack)) $this->structStack[key($this->structStack)] = '-';
            $r = $c = $o;
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
        case T_STATIC:
            $t = $this->getNextToken();
            switch ($t[0])
            {
            case T_STATIC:
            case T_VAR:
            case T_PUBLIC:
            case T_PROTECTED:
            case T_PRIVATE:
            case T_FUNCTION:
                break;
            default:
                break 2;
            }
            // No break;
        case T_VAR:
        case T_PUBLIC:
        case T_PROTECTED:
        case T_PRIVATE:
        case T_CLASS:
        case T_FUNCTION:
            switch ($this->prevType)
            {
            case T_AS:
            case T_FINAL:
            case T_ABSTRACT:
            case T_STATIC:
            case T_VAR:
            case T_PUBLIC:
            case T_PROTECTED:
            case T_PRIVATE:
                break 2;
            }
            // No break;
        case T_CONST:
        case T_USE:
        case T_FINAL:
        case T_ABSTRACT:
        case T_INTERFACE:
        case T_TRAIT:
        case T_FOR:
        case T_FOREACH:
        case T_DO:
        case T_IF:
        case T_SWITCH:
            $r = $c;
            break;

        case T_WHILE:
            if (T_DO !== end($this->structStack)) $r = $c;
            break;

        case T_CATCH:
        case T_ELSE:
        case T_ELSEIF:
        case T_CASE:
        case T_DEFAULT:
            $r = $c = $o;
            break;
        }

        return $r;
    }

    protected function tagNonSemantic(&$token)
    {
        if (' ' === $token[1]) return;

        switch ($token[0])
        {
        case T_WHITESPACE:
        case T_COMMENT:
            // Remove new lines.
            // The process is currently non-bijective,
            // but this can be changed.
            $token[1] = ' ';
            break;

        case T_DOC_COMMENT:
            $token[1] = "\n" . $token[1];
            break;
        }

    }
}

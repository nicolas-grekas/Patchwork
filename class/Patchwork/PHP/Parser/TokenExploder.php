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
 */
class Patchwork_PHP_Parser_TokenExploder extends Patchwork_PHP_Parser
{
    protected

    $callbacks = array(
        '~tagSemantic' => T_SEMANTIC,
        '~tagNonSemantic' => T_NON_SEMANTIC,
    );


    protected function tagSemantic(&$token)
    {
        // Checks if a new line can be prepended to the current token

        if (isset($token[0][0])) switch ($token[0])
        {
        case '"':
        case '`':
        case '[':
            if ($this->inString & 1) return;
            break;
        }
        else switch ($token[0])
        {
        case T_INLINE_HTML:
            return $this->tagNonSemantic($token);

        case T_VARIABLE:
            if ($this->inString & 1) return;
            break;

        case T_OPEN_TAG:
        case T_NUM_STRING:
        case T_KEY_STRING:
        case T_CURLY_OPEN:
        case T_CURLY_CLOSE:
        case T_INLINE_HTML:
        case T_END_HEREDOC:
        case T_STRING_VARNAME:
        case T_OPEN_TAG_WITH_ECHO:
        case T_ENCAPSED_AND_WHITESPACE:
        case T_DOLLAR_OPEN_CURLY_BRACES:
            return;
        }

        // Checks if a new line can be appended to the previous token

        if (false === $this->lastType) return;
        else if (isset($this->lastType[0])) switch ($this->lastType)
        {
        case ']':
            if ($this->inString & 1) return;
            break;

        case '"':
        case '`':
            return;
        }
        else switch ($this->lastType)
        {
        case T_VARIABLE:
            if ($this->inString & 1) return;
            break;

        case T_CLOSE_TAG:
        case T_NUM_STRING:
        case T_KEY_STRING:
        case T_CURLY_OPEN:
        case T_CURLY_CLOSE:
        case T_INLINE_HTML:
        case T_START_HEREDOC:
        case T_STRING_VARNAME:
        case T_ENCAPSED_AND_WHITESPACE:
        case T_DOLLAR_OPEN_CURLY_BRACES:
            return;
        }

        $token[1] = "\n" . $token[1];
    }

    protected function tagNonSemantic(&$token)
    {
    }
}

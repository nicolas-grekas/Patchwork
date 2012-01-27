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
 * The ShortArray parser backports the short array syntax introduced in PHP 5.4.
 */
class Patchwork_PHP_Parser_ShortArray extends Patchwork_PHP_Parser
{
    protected

    $callbacks = array('openBracket' => '['),
    $dependencies = 'BracketBalancer';

    protected function openBracket(&$token)
    {
        switch ($this->lastType)
        {
        case '}':
            $token =& $this->types;
            end($token);
            while ('}' === current($token)) prev($token);
            switch (current($token)) {case ';': case '{': break 2;}

        case ')': case ']': case T_VARIABLE: case T_STRING:
            return;
        }

        $token[1] = 'array(';
        $this->register(array('closeBracket' => T_BRACKET_CLOSE));
    }

    protected function closeBracket(&$token)
    {
        $token[1] = ')';
    }
}

<?php /****************** vi: set fenc=utf-8 ts=4 sw=4 et: *****************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/lgpl.txt GNU/LGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Lesser General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class Patchwork_PHP_Parser_ShortArray extends Patchwork_PHP_Parser
{
    protected

    $stack = array(),
    $callbacks = array(
        'openBracket'  => '[',
        'closeBracket' => ']',
    );

    protected function openBracket(&$token)
    {
        if ($this->stack[] = T_VARIABLE !== $this->lastType)
            return $this->unshiftTokens(array(T_ARRAY, 'array'), '(');
    }

    protected function closeBracket(&$token)
    {
        if (array_pop($this->stack))
            return $this->unshiftTokens(')');
    }
}

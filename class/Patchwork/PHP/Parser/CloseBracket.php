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


class Patchwork_PHP_Parser_CloseBracket extends Patchwork_PHP_Parser
{
    protected

    $level = -1,
    $callbacks = array(
        'incLevel' => array('(', '{', '[', '?'),
        'decLevel' => array(')', '}', ']', ':', ',', T_AS, T_CLOSE_TAG, ';'),
    );


    protected function incLevel(&$token)
    {
        ++$this->level;
    }

    protected function decLevel(&$token)
    {
        switch ($token[0])
        {
        case ',': if ($this->level) break;

        case ')':
        case '}':
        case ']':
        case ':': if ($this->level--) break;

        case ';':
        case T_AS:
        case T_CLOSE_TAG:
            $this->unregister();
            return $this->unshiftTokens(')', $token);
        }
    }
}

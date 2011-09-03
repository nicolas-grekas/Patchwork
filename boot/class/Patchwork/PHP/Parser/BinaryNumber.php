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


class Patchwork_PHP_Parser_BinaryNumber extends Patchwork_PHP_Parser
{
    protected function getTokens($code)
    {
        if (false !== $i = stripos($code, '0b'))
            if ('0b' === strtolower(rtrim(substr($code, $i, 3), '01')))
                $this->register(array('catch0b' => T_LNUMBER));

        return parent::getTokens($code);
    }

    protected function catch0b(&$token)
    {
        if ('0' === $token[1] && $t =& $this->tokens)
        {
            $m = $t[$this->index];

            if (T_STRING === $m[0] && preg_match("'^[bB]([01]+)(.*)'", $m[1], $m))
            {
                $token[1] = '0x' . dechex(bindec($m[1]));

                if (empty($m[2])) unset($t[$this->index++]);
                else $t[$this->index][1] = $m[2];
            }
        }
    }
}

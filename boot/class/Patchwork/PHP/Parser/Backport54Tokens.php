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


// New tokens since PHP 5.4
defined('T_TRAIT') || Patchwork_PHP_Parser::createToken('T_TRAIT');
defined('T_CALLABLE') || Patchwork_PHP_Parser::createToken('T_CALLABLE');
defined('T_INSTEADOF') || Patchwork_PHP_Parser::createToken('T_INSTEADOF');
defined('T_TRAIT_C') || Patchwork_PHP_Parser::createToken('T_TRAIT_C');


class Patchwork_PHP_Parser_Backport54Tokens extends Patchwork_PHP_Parser
{
    protected $callbacks = array('tagString' => T_STRING);

    protected function tagString(&$token)
    {
        switch (strtolower($token[1]))
        {
        case 'trait': $token[0] = T_TRAIT; break;
        case 'callable': $token[0] = T_CALLABLE; break;
        case 'insteadof': $token[0] = T_INSTEADOF; break;
        case '__trait__': $token[0] = T_TRAIT_C; break;
        default: return;
        }

        return $this->unshiftTokens($token);
    }
}

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


// New tokens since PHP 5.3
defined('T_GOTO')         || Patchwork_PHP_Parser::createToken('T_GOTO');
defined('T_DIR' )         || Patchwork_PHP_Parser::createToken('T_DIR');
defined('T_NS_C')         || Patchwork_PHP_Parser::createToken('T_NS_C');
defined('T_NAMESPACE')    || Patchwork_PHP_Parser::createToken('T_NAMESPACE');


class Patchwork_PHP_Parser_Backport53Tokens extends Patchwork_PHP_Parser
{
    protected $callbacks = array('tagString' => T_STRING);

    protected function tagString(&$token)
    {
        switch ($token[1])
        {
        case 'goto':          $token[0] = T_GOTO;      break;
        case 'namespace':     $token[0] = T_NAMESPACE; break;
        case '__DIR__':       $token[0] = T_DIR;       break;
        case '__NAMESPACE__': $token[0] = T_NS_C;      break;
        default: return;
        }

        return $this->unshiftTokens($token);
    }
}

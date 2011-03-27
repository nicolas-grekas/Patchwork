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


// New tokens since PHP 5.3
defined('T_GOTO')         || Patchwork_PHP_Parser::createToken('T_GOTO');
defined('T_DIR' )         || Patchwork_PHP_Parser::createToken('T_DIR');
defined('T_NS_C')         || Patchwork_PHP_Parser::createToken('T_NS_C');
defined('T_NAMESPACE')    || Patchwork_PHP_Parser::createToken('T_NAMESPACE');
defined('T_NS_SEPARATOR') || Patchwork_PHP_Parser::createToken('T_NS_SEPARATOR');


class Patchwork_PHP_Parser_Backport53 extends Patchwork_PHP_Parser
{
    protected $callbacks = array('tagNew' => T_NEW);


    protected function getTokens($code)
    {
        $code = parent::getTokens($code);
        $i = 0;

        while (isset($code[++$i]))
        {
            if (isset($code[$i][1])) switch ($code[$i][1])
            {
            case 'goto':          $code[$i][0] = T_GOTO;         break;
            case 'namespace':     $code[$i][0] = T_NAMESPACE;    break;
            case '__DIR__':       $code[$i][0] = T_DIR;          break;
            case '__NAMESPACE__': $code[$i][0] = T_NS_C;         break;
            case '\\':            $code[$i][0] = T_NS_SEPARATOR; break;
            }
        }

        return $code;
    }

    protected function tagNew(&$token)
    {
        // Fix `new $foo`, when $foo = 'ns\class';
        // TODO: new ${...}, new $foo[...] and new $foo->...

        $t =& $this->getNextToken($n);

        if (T_VARIABLE === $t[0])
        {
            $n = $this->getNextToken($n);

            if ('[' !== $n[0] && T_OBJECT_OPERATOR !== $n[0])
            {
                $t[1] = "\${is_string($\x9D={$t[1]})&&($\x9D=strtr($\x9D,'\\\\','_'))?\"\x9D\":\"\x9D\"}";
            }
        }
    }
}

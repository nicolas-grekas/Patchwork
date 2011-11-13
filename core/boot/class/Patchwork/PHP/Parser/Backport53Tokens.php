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

defined('T_DIR') || Patchwork_PHP_Parser::createToken('T_DIR');
defined('T_GOTO') || Patchwork_PHP_Parser::createToken('T_GOTO');
defined('T_NS_C') || Patchwork_PHP_Parser::createToken('T_NS_C');
defined('T_NAMESPACE') || Patchwork_PHP_Parser::createToken('T_NAMESPACE');

/**
 * The Backport53Tokens parser backports tokens introduced in PHP 5.3.
 */
class Patchwork_PHP_Parser_Backport53Tokens extends Patchwork_PHP_Parser
{
    protected $dependencies = 'StringInfo';

    function __construct(parent $parent)
    {
        parent::__construct($parent);

        $this->dependencies['StringInfo']->addReservedTokens(array(
            'goto' => T_GOTO,
            '__dir__' => T_DIR,
            'namespace' => T_NAMESPACE,
            '__namespace__' => T_NS_C,
        ));
    }
}

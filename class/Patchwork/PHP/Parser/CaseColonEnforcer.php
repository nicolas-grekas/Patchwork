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
 * The CaseColonEnforcer parser enforces colons instead of semicolons for case statements
 */
class Patchwork_PHP_Parser_CaseColonEnforcer extends Patchwork_PHP_Parser
{
    protected

    $caseStack = array(),
    $callbacks = array(
        '~tagCase' => T_CASE,
    ),
    $dependencies = array('BracketWatcher' => 'brackets');


    protected function tagCase(&$token)
    {
        $this->caseStack or $this->register(array('tagColon' => array(';', ':')));
        $this->caseStack[] = count($this->brackets);
    }

    protected function tagColon(&$token)
    {
        if (count($this->brackets) === end($this->caseStack))
        {
            array_pop($this->caseStack);
            $this->caseStack or $this->unregister(array('tagColon' => array(';', ':')));
            if (';' === $token[0]) return $this->unshiftTokens(':');
        }
    }
}

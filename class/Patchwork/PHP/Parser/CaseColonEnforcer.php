<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

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

    $brackets,
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

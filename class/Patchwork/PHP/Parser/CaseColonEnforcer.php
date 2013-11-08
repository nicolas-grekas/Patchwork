<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork\PHP\Parser;

use Patchwork\PHP\Parser;

/**
 * The CaseColonEnforcer parser enforces colons instead of semicolons for case statements.
 */
class CaseColonEnforcer extends Parser
{
    protected

    $caseStack = array(),
    $callbacks = array(
        '~tagCase' => T_CASE,
    ),

    $bracketsCount,
    $dependencies = array('BracketWatcher' => 'bracketsCount');


    protected function tagCase(&$token)
    {
        $this->caseStack or $this->register(array('tagColon' => array(';', ':')));
        $this->caseStack[] = $this->bracketsCount;
    }

    protected function tagColon(&$token)
    {
        if ($this->bracketsCount === end($this->caseStack))
        {
            array_pop($this->caseStack);
            $this->caseStack or $this->unregister(array('tagColon' => array(';', ':')));
            if (';' === $token[0]) return $this->unshiftTokens(':');
        }
    }
}

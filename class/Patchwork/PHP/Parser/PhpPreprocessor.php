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
 * The PhpPreprocessor parser applies a stream filter to require instructions
 */
class PhpPreprocessor extends Parser
{
    protected

    $prependedCode = '',
    $exprStack = array(),
    $exprLevel,
    $exprCallbacks = array(
        '~incExprLevel' => array('(', '{', '[', '?'),
        'decExprLevel' => array(')', '}', ']', ':', ',', T_AS, T_CLOSE_TAG, ';'),
    ),
    $callbacks = array(
        '~tagRequire' => array(T_REQUIRE_ONCE, T_INCLUDE_ONCE, T_REQUIRE, T_INCLUDE),
    );


    function __construct(parent $parent = null, $prepended_code)
    {
        if ($prepended_code) $this->prependedCode = $prepended_code;
        if ($this->prependedCode) $this->prependedCode .= '(';
        else unset($this->callbacks['~tagRequire']);
        parent::__construct($parent);
    }

    protected function tagRequire(&$token)
    {
        $this->unshiftCode($this->prependedCode);
        if (isset($this->exprLevel)) $this->exprStack[] = $this->exprLevel;
        else $this->register($this->exprCallbacks);
        $this->exprLevel = -1;
    }

    protected function incExprLevel(&$token)
    {
        ++$this->exprLevel;
    }

    protected function decExprLevel(&$token)
    {
        switch ($token[0])
        {
        case ',': if ($this->exprLevel) break;

        case ')':
        case '}':
        case ']':
        case ':': if ($this->exprLevel--) break;

        default:
            $this->exprLevel = array_pop($this->exprStack);
            if (!isset($this->exprLevel)) $this->unregister($this->exprCallbacks);
            return $this->unshiftTokens(')', $token);
        }
    }
}

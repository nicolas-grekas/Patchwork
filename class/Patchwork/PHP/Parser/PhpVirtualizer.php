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
 * The PhpVirtualizer parser applies a stream filter to require instructions
 *
 * @todo: nested require, like in require (require $file)
 */
class Patchwork_PHP_Parser_PhpVirtualizer extends Patchwork_PHP_Parser
{
    protected

    $prependedTokens = array(),
    $exprLevel,
    $exprCallbacks = array(
        '~incExprLevel' => array('(', '{', '[', '?'),
        'decExprLevel' => array(')', '}', ']', ':', ',', T_AS, T_CLOSE_TAG, ';'),
    ),
    $callbacks = array(
        'tagRequire' => array(T_REQUIRE_ONCE, T_INCLUDE_ONCE, T_REQUIRE, T_INCLUDE),
    );


    function __construct(parent $parent, $filter_prefix)
    {
        if ($filter_prefix) $this->prependedTokens = array(array(T_STRING, self::export($filter_prefix)), '.');
        if ($this->prependedTokens) $this->prependedTokens[] = '(';
        else unset($this->callbacks['tagRequire']);
        parent::__construct($parent);
    }

    protected function tagRequire(&$token)
    {
        call_user_func_array(array($this, 'unshiftTokens'), $this->prependedTokens);
        $this->register($this->exprCallbacks);
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
            $this->unregister($this->exprCallbacks);
            return $this->unshiftTokens(')', $token);
        }
    }
}

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
 * The DocCommentInfo follows doc comments and exposes them to other parsers.
 */
class DocCommentInfo extends Parser
{
    protected

    $docComment = false,
    $callbacks = array('~tagDocComment' => T_DOC_COMMENT),
    $resetCallbacks = array(
        '~expandResetCallbacks' => array(T_VAR, T_STATIC, T_PRIVATE, T_PROTECTED, T_PUBLIC, T_NAMESPACE, T_CLASS, T_INTERFACE, T_TRAIT, T_FUNCTION),
        '~resetDocComment' => array('}'),
    ),
    $expandedResetCallbacks = array(
        '~resetDocComment' => array(',', ';', '{', '(', '='),
    );


    protected function tagDocComment(&$token)
    {
        if (false === $this->docComment) $this->register($this->resetCallbacks);
        $this->docComment = $token[1];
    }

    protected function expandResetCallbacks(&$token)
    {
        $this->unregister($this->resetCallbacks);
        $this->register($this->expandedResetCallbacks);
    }

    protected function resetDocComment(&$token)
    {
        $this->docComment = false;
        $this->unregister($this->resetCallbacks);
        $this->unregister($this->expandedResetCallbacks);
    }
}

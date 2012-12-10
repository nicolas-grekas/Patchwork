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
 * The DocCommentInfo follows doc comments and exposes them to other parsers.
 *
 * @todo Follow more strictly the way Reflection*->getDocComment() is fed
 */
class Patchwork_PHP_Parser_DocCommentInfo extends Patchwork_PHP_Parser
{
    protected

    $docComment = false,
    $callbacks = array('~tagDocComment' => T_DOC_COMMENT),
    $resetCallbacks = array('~resetDocComment' => array('}', T_NAMESPACE, T_CLASS, T_INTERFACE, T_TRAIT, T_FUNCTION));


    protected function tagDocComment(&$token)
    {
        if (false === $this->docComment) $this->register($this->resetCallbacks);
        $this->docComment = $token[1];
    }

    protected function resetDocComment(&$token)
    {
        $this->docComment = false;
        $this->unregister($this->resetCallbacks);
    }
}

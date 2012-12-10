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
 * The WorkaroundBug55156 parser inserts empty doc comment to workaround https://bugs.php.net/55156
 */
class Patchwork_PHP_Parser_WorkaroundBug55156 extends Patchwork_PHP_Parser
{
    public

    $targetPhpVersionId = -50308;

    protected

    $callbacks = array('~tagClass' => array(T_CLASS, T_INTERFACE)),
    $dependencies = array('DocCommentInfo' => 'docComment'),
    $docComment = false;


    protected function tagClass(&$token)
    {
        if (false === $this->docComment) $token[1] = '/** */' . $token[1];
    }
}

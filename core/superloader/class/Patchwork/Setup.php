<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork;

/**
 * Setup is a hook executed only once, when application is initialized.
 */
class Setup
{
    protected static function execute()
    {
    }

    static function hook()
    {
        $G = $_GET; $P = $_POST; $C = $_COOKIE; $F = $_FILES;
        $_GET = $_POST = $_COOKIE = $_FILES = array();

        self::execute();

        $_GET = $G; $_POST = $P; $_COOKIE = $C; $_FILES = $F;
    }
}

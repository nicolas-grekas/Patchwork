<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class agent_fckeditor_js extends agent
{
    const contentType = 'text/javascript';

    protected $maxage = -1;

    function control() {}

    function compose($o)
    {
        $o->DATA = file_get_contents(patchworkPath('public/__/fckeditor/src/fckeditor.js'));
        return $o;
    }
}

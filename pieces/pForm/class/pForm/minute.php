<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pForm_minute extends pForm_text
{
    protected

    $maxlength = 2,
    $maxint = 59;


    protected function get()
    {
        $a = parent::get();
        $a->onchange = "this.value=+this.value||'';if(this.value<0||this.value>{$this->maxint})this.value=''";
        return $a;
    }
}

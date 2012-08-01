<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class agent_css extends agent
{
    const contentType = 'text/css';

    public $get = '__0__';

    protected

    $maxage = -1,
    $watch = array('public/css'),
    $extension = '.css';


    function control()
    {
        $dir = substr(get_class($this), 6);
        $dir = Patchwork\Superloader::class2file($dir);

        $tpl = $this->get->__0__;

        if ($tpl !== '')
        {
            if ($this->extension !== substr($tpl, -3)) $tpl .= $this->extension;

            $tpl = str_replace('../', '/', $dir . '/' . strtr($tpl, '\\', '/'));
        }
        else $tpl = $dir . $this->extension;

        $this->template = $tpl;
    }
}

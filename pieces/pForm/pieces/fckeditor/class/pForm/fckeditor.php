<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pForm_fckeditor extends pForm_textarea
{
    protected

    $toolbarSet,
    $config;


    protected function init(&$param)
    {
        if (isset($this->form->rawValues[$this->name]))
        {
            $value =& $this->form->rawValues[$this->name];
            $value = FILTER::get($value, 'html');
        }

        parent::init($param);

        if (isset($param['toolbarSet'])) $this->toolbarSet = $param['toolbarSet'];
        if (isset($param['config'])) $this->config = $param['config'];
    }

    protected function get()
    {
        $a = parent::get();

        $this->agent = 'form/fckeditor';

        if (isset($this->toolbarSet)) $a->_toolbarSet = $this->toolbarSet;
        if (isset($this->config)) $a->_config = $this->config;

        return $a;
    }
}

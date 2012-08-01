<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pForm_QSelect extends pForm_text
{
    protected

    $src = '',
    $lock = 0,
    $textarea = 0;


    protected function init(&$param)
    {
        isset($param['src'])  && $this->src  = $param['src'];
        empty($param['lock']) || $this->lock = 1;

        if (isset($param['textarea']))
        {
            $this->textarea = (int) (bool) $param['textarea'];
        }
        else if (isset($param['valid']) && 'text' === strtolower($param['valid'])) $this->textarea = 1;

        if ($this->textarea)
        {
            $this->maxlength = 65635;
            isset($param['valid']) || $param['valid'] = 'text';
        }

        parent::init($param);
    }

    protected function get()
    {
        $a = parent::get();

        $this->agent = 'QSelect/input';

        $a->_src = $this->src;
        $this->textarea && $a->_textarea = 1;
        $this->lock     && $a->_lock     = 1;

        return $a;
    }
}

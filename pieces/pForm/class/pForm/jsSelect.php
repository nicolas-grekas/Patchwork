<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pForm_jsSelect extends pForm_select
{
    protected $src;

    protected function init(&$param)
    {
        unset($param['item']);
        unset($param['sql']);
        isset($param['valid']) || $param['valid'] = 'char';

        parent::init($param);

        isset($param['src']) && $this->src = $param['src'];
    }

    protected function get()
    {
        $a = parent::get();

        $this->agent = 'form/jsSelect';

        if (isset($this->src)) $a->_src_ = $this->src;

        if ($this->status) $a->_value = new loop_array((array) $this->value, false);

        unset($a->_type);

        return $a;
    }
}

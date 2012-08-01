<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pForm_text extends pForm_hidden
{
    protected

    $type = 'text',
    $maxlength = 255;


    protected function init(&$param)
    {
        parent::init($param);

        if (isset($param['maxlength']) && $param['maxlength'] > 0)
        {
            $this->maxlength = (int) $param['maxlength'];
        }

        if (mb_strlen($this->value) > $this->maxlength)
        {
            $this->value = mb_substr($this->value, 0, $this->maxlength);
        }
    }

    protected function get()
    {
        $a = parent::get();
        if ($this->maxlength) $a->maxlength = $this->maxlength;
        return $a;
    }

    protected function addJsValidation($a)
    {
        $a->_valid = new loop_array(array_merge(array($this->valid), $this->validArgs));
        return $a;
    }
}

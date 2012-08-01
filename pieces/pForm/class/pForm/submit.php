<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pForm_submit extends pForm_hidden
{
    protected

    $type = 'submit',
    $isdata = false;


    protected function init(&$param)
    {
        empty($param['disabled']) || $this->disabled = true;
        if ($this->disabled || !empty($param['readonly'])) $this->readonly = true;

        if ($this->readonly) {}
        else if (isset($this->form->rawValues[$this->name])) $this->status = true;
        else if (isset($this->form->rawValues[$this->name . '_x']) && isset($this->form->rawValues[$this->name . '_y']))
        {
            $x =& $this->form->rawValues;

            $this->value = array(
                isset($x[$this->name . '_x']) ? (int) $x[$this->name . '_x'] : 0,
                isset($x[$this->name . '_y']) ? (int) $x[$this->name . '_y'] : 0,
            );

            unset($x);

            $x = $this->value[0];
            $y = $this->value[1];

            $this->status = false !== $x && false !== $y;
            $this->value = $this->status ? array($x, $y) : array();
        }
        else $this->status = '';

        $this->form->setEnterControl($this->name);
    }

    protected function get()
    {
        $a = parent::get();
        unset($a->value);
        return $a;
    }
}

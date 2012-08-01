<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


class pForm_date extends pForm_text
{
    protected $maxlength = 10;

    protected function init(&$param)
    {
        isset($param['valid']) || $param['valid'] = 'date';

        parent::init($param);
    }

    function setValue($value)
    {
        $this->value = 0 === strncmp($value, '0000-00-00', 10) ? '' : substr($value, 0, 10);
    }

    protected function get()
    {
        $a = parent::get();
        $a->onchange = 'this.value=valid_date(this.value)';
        $a->_placeholder = T('jj-mm-aaaa');
        $a->_class = 'text date';
        return $a;
    }

    function getDbValue()
    {
        if ($v = $this->getValue())
        {
            if (preg_match("'^(\d{2})-(\d{2})-(\d{4})$'", $v, $v))
            {
                $v = $v[3] . '-' . $v[2] . '-' . $v[1];
            }
            else $v = '';
        }

        return (string) $v;
    }
}

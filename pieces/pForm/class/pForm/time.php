<?php // vi: set fenc=utf-8 ts=4 sw=4 et:
/*
 * Copyright (C) 2012 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */


/* interface is out of date
class pForm_time extends pForm_text
{
    protected $maxlength = 2;
    protected $maxint = 23;
    protected $minute;

    protected function init(&$param)
    {
        $param['valid'] = 'int';
        $param[0] = 0; $param[1] = 23;
        parent::init($param);

        $this->minute = $form->add('minute', $name.'_minute', array('valid'=>'int', 0, 59));
    }

    function getValue()
    {
        return $this->status ? 60*(60*$this->value + ($this->minute->status ? $this->minute->value : 0)) : 0;
    }
}
*/
